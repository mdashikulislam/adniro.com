<?php

namespace App\Helpers\Common\HierarchicalData\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NestedSetManager
{
	protected string $parentColumn = 'parent_id';
	protected string $leftColumn = 'lft';
	protected string $rightColumn = 'rgt';
	protected string $depthColumn = 'depth';
	
	/**
	 * Insert a new node as a child of the given parent ID (or as root).
	 *
	 * Wrap the entire process in a transaction to ensure consistency
	 * if something fails halfway.
	 */
	public function insertNode(string $modelClass, array $attributes, ?int $parentId = null): Model
	{
		/** @var Model $modelClass */
		return DB::transaction(function () use ($modelClass, $attributes, $parentId) {
			if (is_null($parentId)) {
				return $this->insertRoot($modelClass, $attributes);
			}
			
			$parent = $modelClass::findOrFail($parentId);
			$parentLeft = $parent->{$this->leftColumn};
			$parentRight = $parent->{$this->rightColumn};
			$parentDepth = $parent->{$this->depthColumn};
			
			// Shift boundaries
			$modelClass::where($this->rightColumn, '>=', $parentRight)->increment($this->rightColumn, 2);
			$modelClass::where($this->leftColumn, '>', $parentRight)->increment($this->leftColumn, 2);
			
			// Create the new node
			$newNode = new $modelClass($attributes);
			$newNode->{$this->parentColumn} = $parentId;
			$newNode->{$this->leftColumn} = $parentRight;
			$newNode->{$this->rightColumn} = $parentRight + 1;
			$newNode->{$this->depthColumn} = $parentDepth + 1;
			$newNode->save();
			
			return $newNode;
		});
	}
	
	/**
	 * Insert a new node as a root (no parent).
	 */
	protected function insertRoot(string $modelClass, array $attributes): Model
	{
		/** @var Model $modelClass */
		// This could also be wrapped in a transaction, but it's already inside
		// insertNode's transaction if that's how it's being called.
		$maxRight = $modelClass::max($this->rightColumn) ?? 0;
		
		$newNode = new $modelClass($attributes);
		$newNode->{$this->parentColumn} = null;
		$newNode->{$this->leftColumn} = $maxRight + 1;
		$newNode->{$this->rightColumn} = $maxRight + 2;
		$newNode->{$this->depthColumn} = 0;
		$newNode->save();
		
		return $newNode;
	}
	
	/**
	 * Delete a node (and all of its descendants).
	 *
	 * Use a transaction to ensure consistency.
	 */
	public function deleteNode(string $modelClass, int $nodeId): void
	{
		/** @var Model $modelClass */
		DB::transaction(function () use ($modelClass, $nodeId) {
			$node = $modelClass::findOrFail($nodeId);
			$left = $node->{$this->leftColumn};
			$right = $node->{$this->rightColumn};
			$width = $right - $left + 1;
			
			// 1) Delete all descendants (and the node itself)
			$modelClass::where($this->leftColumn, '>=', $left)->where($this->rightColumn, '<=', $right)->delete();
			
			// 2) Shift everything on the right back
			$modelClass::where($this->rightColumn, '>', $right)->decrement($this->rightColumn, $width);
			$modelClass::where($this->leftColumn, '>', $right)->decrement($this->leftColumn, $width);
		});
	}
	
	/**
	 * Move an existing node (and its entire subtree) under a new parent.
	 *
	 * Use a transaction to keep partial updates from corrupting the tree.
	 */
	public function moveNode(string $modelClass, int $nodeId, int $newParentId): void
	{
		/** @var Model $modelClass */
		DB::transaction(function () use ($modelClass, $nodeId, $newParentId) {
			$node = $modelClass::findOrFail($nodeId);
			$newParent = $modelClass::findOrFail($newParentId);
			
			// Basic check: ensure we don't move a node into its own subtree
			if (
				$newParent->{$this->leftColumn} >= $node->{$this->leftColumn}
				&& $newParent->{$this->rightColumn} <= $node->{$this->rightColumn}
			) {
				throw new RuntimeException('Cannot move a node inside its own subtree.');
			}
			
			$nodeLeft = $node->{$this->leftColumn};
			$nodeRight = $node->{$this->rightColumn};
			$width = $nodeRight - $nodeLeft + 1;
			
			// 1) Temporarily remove the subtree (map it to [1..$width])
			$modelClass::where($this->leftColumn, '>=', $nodeLeft)
				->where($this->rightColumn, '<=', $nodeRight)
				->update([
					$this->leftColumn  => DB::raw($this->leftColumn . ' - ' . ($nodeLeft - 1)),
					$this->rightColumn => DB::raw($this->rightColumn . ' - ' . ($nodeLeft - 1)),
				]);
			
			// 2) Close the gap in the original tree
			$modelClass::where($this->rightColumn, '>', $nodeRight)->decrement($this->rightColumn, $width);
			$modelClass::where($this->leftColumn, '>', $nodeRight)->decrement($this->leftColumn, $width);
			
			// 3) Make room at the new parent's position
			$newParent->refresh();
			$parentRight = $newParent->{$this->rightColumn};
			
			$modelClass::where($this->rightColumn, '>=', $parentRight)->increment($this->rightColumn, $width);
			$modelClass::where($this->leftColumn, '>', $parentRight)->increment($this->leftColumn, $width);
			
			// 4) Shift the subtree from [1..$width] to the new location
			$offset = $parentRight - 1;
			
			$modelClass::where($this->leftColumn, '<=', $width)
				->where($this->rightColumn, '<=', $width)
				->update([
					$this->leftColumn  => DB::raw($this->leftColumn . ' + ' . $offset),
					$this->rightColumn => DB::raw($this->rightColumn . ' + ' . $offset),
				]);
			
			// 5) Update the root node of the moved subtree
			$node->refresh();
			$node->{$this->parentColumn} = $newParentId;
			$node->{$this->depthColumn} = $newParent->{$this->depthColumn} + 1;
			$node->save();
			
			// 6) Update depth on all children in that subtree
			$this->updateSubtreeDepths($modelClass, $node);
		});
	}
	
	/**
	 * Recalculate `depth` for the entire subtree under the given node.
	 * (No transaction needed here because it's already called within the move transaction.)
	 */
	protected function updateSubtreeDepths(string $modelClass, Model $rootNode): void
	{
		/** @var Model $modelClass */
		$queue = [$rootNode];
		while ($queue) {
			/** @var Model $current */
			$current = array_shift($queue);
			
			// Get immediate children
			$children = $modelClass::where($this->parentColumn, $current->getKey())->get();
			foreach ($children as $child) {
				$child->{$this->depthColumn} = $current->{$this->depthColumn} + 1;
				$child->save();
				$queue[] = $child;
			}
		}
	}
	
	/**
	 * Build a PARTIAL tree (nested array) for the subtree under a given node.
	 */
	public function buildSubtree(string $modelClass, int $nodeId, bool $normalizeDepth = false): array
	{
		/** @var Model $modelClass */
		$node = $modelClass::findOrFail($nodeId);
		
		$subtreeNodes = $modelClass::query()
			->where($this->leftColumn, '>=', $node->{$this->leftColumn})
			->where($this->rightColumn, '<=', $node->{$this->rightColumn})
			->orderBy($this->leftColumn)
			->get();
		
		$depthOffset = $normalizeDepth ? $node->{$this->depthColumn} : 0;
		
		return $this->buildNestedArray($subtreeNodes, $depthOffset);
	}
	
	/**
	 * Build a tree (nested array) of ALL nodes, sorted by `lft`.
	 */
	public function buildTree(string $modelClass): array
	{
		/** @var Model $modelClass */
		$allNodes = $modelClass::orderBy($this->leftColumn)->get();
		
		return $this->buildNestedArray($allNodes);
	}
	
	/**
	 * Converts a collection of nodes (already ordered by lft) into a nested array.
	 */
	protected function buildNestedArray($nodes, int $depthOffset = 0): array
	{
		$tree = [];
		$stack = [];
		
		foreach ($nodes as $node) {
			// Adjust depth if we're offsetting
			$depth = $node->{$this->depthColumn} - $depthOffset;
			
			$item = [
				'id'       => $node->getKey(),
				'name'     => $node->name ?? '',
				'depth'    => $depth,
				'children' => [],
			];
			
			if (empty($stack)) {
				$tree[] = $item;
				$stack[] = &$tree[array_key_last($tree)];
			} else {
				// While the top of the stack has a depth >= current node’s depth, pop it
				while (!empty($stack) && end($stack)['depth'] >= $depth) {
					array_pop($stack);
				}
				
				// If we still have items in the stack, the top is our parent
				if (!empty($stack)) {
					// Reference the top item in the stack (the parent)
					$parentIndex = count($stack) - 1;
					$stack[$parentIndex]['children'][] = $item;
					
					// Now push a reference to the newly added child
					$childIndex = count($stack[$parentIndex]['children']) - 1;
					$stack[] = &$stack[$parentIndex]['children'][$childIndex];
				} else {
					// No parent in the stack => it's a new root in this subset
					$tree[] = $item;
					
					// Push a reference to this new root onto the stack
					$rootIndex = count($tree) - 1;
					$stack[] = &$tree[$rootIndex];
				}
			}
		}
		
		return $tree;
	}
}
