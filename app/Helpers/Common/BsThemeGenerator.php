<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Helpers\Common;

class BsThemeGenerator
{
	private string $primaryColor;
	private string|false $template;
	
	public function __construct(string $primaryColor)
	{
		$this->primaryColor = $this->normalizeHexColor($primaryColor);
		$pathDir = resource_path('views/front/common/css/');
		$filePath = $pathDir . 'primary-color.css';
		$this->template = file_get_contents($filePath);
	}
	
	public function generateCSS(): string
	{
		$rgb = $this->hexToRgb($this->primaryColor);
		$variations = $this->generateBootstrapColorVariations($this->primaryColor);
		
		// Create primary color without hash for SVG usage
		$primaryColorNoHash = ltrim($this->primaryColor, '#');
		
		$replacements = [
			'var(--PRIMARY_COLOR)'         => $this->primaryColor,
			'var(--PRIMARY_COLOR_NO_HASH)' => $primaryColorNoHash,
			'var(--PRIMARY_RGB)'           => implode(', ', $rgb),
			'var(--PRIMARY_100)'           => $variations['100'],
			'var(--PRIMARY_200)'           => $variations['200'],
			'var(--PRIMARY_300)'           => $variations['300'],
			'var(--PRIMARY_400)'           => $variations['400'],
			'var(--PRIMARY_600)'           => $variations['600'],
			'var(--PRIMARY_700)'           => $variations['700'],
			'var(--PRIMARY_800)'           => $variations['800'],
			'var(--PRIMARY_900)'           => $variations['900'],
			'var(--PRIMARY_TEXT_EMPHASIS)' => $variations['700'],
			'var(--PRIMARY_BG_SUBTLE)'     => $variations['100'],
			'var(--PRIMARY_BORDER_SUBTLE)' => $variations['200'],
			'var(--LINK_COLOR)'            => $this->primaryColor,
			'var(--LINK_HOVER_COLOR)'      => $variations['700'],
		];
		
		return str_replace(array_keys($replacements), array_values($replacements), $this->template);
	}
	
	public function saveCSSToFile(string $filename = 'custom-primary-color.css'): string
	{
		$css = $this->generateCSS();
		$written = file_put_contents($filename, $css);
		
		if ($written === false) {
			throw new \RuntimeException("Failed to write CSS file: {$filename}");
		}
		
		return $filename;
	}
	
	public function outputCSS(): void
	{
		header('Content-Type: text/css');
		echo $this->generateCSS();
	}
	
	/**
	 * Generate Bootstrap-style color variations using tint and shade functions
	 */
	private function generateBootstrapColorVariations(string $baseColor): array
	{
		return [
			'100' => $this->tintColor($baseColor, 80),    // Mix with 80% white
			'200' => $this->tintColor($baseColor, 60),    // Mix with 60% white
			'300' => $this->tintColor($baseColor, 40),    // Mix with 40% white
			'400' => $this->tintColor($baseColor, 20),    // Mix with 20% white
			'600' => $this->shadeColor($baseColor, 20),   // Mix with 20% black
			'700' => $this->shadeColor($baseColor, 40),   // Mix with 40% black
			'800' => $this->shadeColor($baseColor, 60),   // Mix with 60% black
			'900' => $this->shadeColor($baseColor, 80),   // Mix with 80% black
		];
	}
	
	/**
	 * Bootstrap's tint-color() function equivalent
	 * Mixes a color with white
	 */
	private function tintColor(string $color, float $weight): string
	{
		return $this->mixColors($color, '#ffffff', $weight);
	}
	
	/**
	 * Bootstrap's shade-color() function equivalent
	 * Mixes a color with black
	 */
	private function shadeColor(string $color, float $weight): string
	{
		return $this->mixColors($color, '#000000', $weight);
	}
	
	/**
	 * Bootstrap's mix() function equivalent
	 * Mixes two colors together based on weight
	 */
	private function mixColors(string $color1, string $color2, float $weight): string
	{
		$weight = max(0, min(100, $weight)) / 100; // Normalize to 0-1
		
		$rgb1 = $this->hexToRgb($color1);
		$rgb2 = $this->hexToRgb($color2);
		
		$mixedRgb = [
			round($rgb1[0] * (1 - $weight) + $rgb2[0] * $weight),
			round($rgb1[1] * (1 - $weight) + $rgb2[1] * $weight),
			round($rgb1[2] * (1 - $weight) + $rgb2[2] * $weight),
		];
		
		return $this->rgbToHex($mixedRgb[0], $mixedRgb[1], $mixedRgb[2]);
	}
	
	/**
	 * Normalize hex color format
	 */
	private function normalizeHexColor(string $hex): string
	{
		$hex = ltrim($hex, '#');
		
		if (!preg_match('/^[0-9a-fA-F]{3}$|^[0-9a-fA-F]{6}$/', $hex)) {
			throw new \InvalidArgumentException("Invalid hex color format: #{$hex}");
		}
		
		// Convert 3-digit hex to 6-digit
		if (strlen($hex) === 3) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		
		return '#' . strtolower($hex);
	}
	
	/**
	 * Convert hex color to RGB array
	 */
	private function hexToRgb(string $hex): array
	{
		$hex = ltrim($hex, '#');
		
		return [
			hexdec(substr($hex, 0, 2)),
			hexdec(substr($hex, 2, 2)),
			hexdec(substr($hex, 4, 2)),
		];
	}
	
	/**
	 * Convert RGB values to hex color
	 */
	private function rgbToHex(int $r, int $g, int $b): string
	{
		$r = max(0, min(255, $r));
		$g = max(0, min(255, $g));
		$b = max(0, min(255, $b));
		
		return sprintf("#%02x%02x%02x", $r, $g, $b);
	}
	
	/**
	 * Get color variations as an array (useful for debugging or API responses)
	 */
	public function getColorVariations(): array
	{
		$variations = $this->generateBootstrapColorVariations($this->primaryColor);
		$variations['500'] = $this->primaryColor; // Base color
		
		ksort($variations);
		
		return $variations;
	}
	
	/**
	 * Get RGB values for the primary color
	 */
	public function getPrimaryRgb(): array
	{
		return $this->hexToRgb($this->primaryColor);
	}
	
	/**
	 * Get the normalized primary color
	 */
	public function getPrimaryColor(): string
	{
		return $this->primaryColor;
	}
	
	/**
	 * Generate a single tinted color
	 */
	public function getTintedColor(float $weight): string
	{
		return $this->tintColor($this->primaryColor, $weight);
	}
	
	/**
	 * Generate a single shaded color
	 */
	public function getShadedColor(float $weight): string
	{
		return $this->shadeColor($this->primaryColor, $weight);
	}
}
