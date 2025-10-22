@extends('admin.layouts.master')

@php
	$title = 'PHP Information';
	
	$authUser = auth()->user();
	$userRoles = $authUser->getRoleNames();
	
	$userRolesHtml = $userRoles->isNotEmpty()
		? $userRoles->map(fn($item) => '<span class="badge bg-success">' . $item . '</span>')->join(' ')
		: '<span class="badge bg-success">Super-Admin Access</span>';
@endphp
@section('header')
	<div class="row page-titles">
		<div class="col-md-6 col-12 align-self-center">
			<h2 class="mb-0">{{ $title }}</h2>
		</div>
		<div class="col-md-6 col-12 align-self-center d-none d-md-flex justify-content-end">
			<ol class="breadcrumb mb-0 p-0 bg-transparent">
				<li class="breadcrumb-item"><a href="{{ urlGen()->adminUrl() }}">{{ trans('admin.dashboard') }}</a></li>
				<li class="breadcrumb-item"><a href="{{ urlGen()->adminUrl('system') }}">{{ trans('admin.system_info') }}</a></li>
				<li class="breadcrumb-item active d-flex align-items-center">{{ $title }}</li>
			</ol>
		</div>
	</div>
@endsection

@section('content')
	
	<div class="row d-flex justify-content-center">
		
		{{-- System Info --}}
		<div class="col-xxl-10 col-xl-11 col-lg-12 col-md-12">
			
			<div class="card shadow-sm mb-4">
				<div class="card-header bg-primary text-white">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<h3 class="mb-0"><i class="fas fa-server me-2"></i>Summary</h3>
							<small class="opacity-75">Server: {{ request()->server('SERVER_NAME') }} | PHP {{ PHP_VERSION }}</small>
						</div>
						<div>
							{!! $userRolesHtml !!}
						</div>
					</div>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-4">
							<div class="d-flex align-items-center">
								<i class="fas fa-clock text-muted me-2"></i>
								<small class="text-muted">Generated: {{ now()->format('M d, Y H:i:s') }}</small>
							</div>
						</div>
						<div class="col-md-4">
							<div class="d-flex align-items-center">
								<i class="fas fa-user text-muted me-2"></i>
								<small class="text-muted">User: {{ $authUser->name }}</small>
							</div>
						</div>
						<div class="col-md-4">
							<div class="d-flex align-items-center">
								<i class="fas fa-database text-muted me-2"></i>
								<small class="text-muted">Environment: {{ app()->environment() }}</small>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- PHP Info Content -->
			<div class="card border-0 shadow-sm">
				<div class="card-body phpinfo-content">
					<div class="row">
						<div class="col-md-12">
							{!! $phpinfo !!}
						</div>
					</div>
				</div>
			</div>
		</div>
	
	</div>

@endsection

@section('after_styles')
	@parent
	<style>
		/* Enhanced phpinfo styling */
		.phpinfo-content table {
			margin-bottom: 1.5rem !important;
			border: 1px solid #dee2e6;
			table-layout: fixed !important; /* Force fixed layout for better wrapping */
			width: 100% !important;
		}
		
		.phpinfo-content table th {
			background-color: #495057 !important;
			color: white !important;
			font-weight: 600;
			padding: 0.75rem !important;
			border: 1px solid #495057 !important;
			vertical-align: middle;
			word-wrap: break-word !important;
			word-break: break-all !important;
			hyphens: auto;
			white-space: normal !important;
		}
		
		.phpinfo-content table td {
			padding: 0.5rem 0.75rem !important;
			border: 1px solid #dee2e6 !important;
			vertical-align: middle;
			word-wrap: break-word !important;
			word-break: break-all !important;
			hyphens: auto;
			white-space: normal !important;
			overflow-wrap: break-word !important;
			/* Set specific widths for better column distribution */
		}
		
		/* Set column widths for better text distribution */
		.phpinfo-content table td:first-child {
			width: 30% !important;
			background-color: #f8f9fa;
			font-weight: 500;
			color: #495057;
			min-width: 150px !important;
		}
		
		.phpinfo-content table td:nth-child(2) {
			width: 35% !important;
			font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
			font-size: 0.85em;
		}
		
		.phpinfo-content table td:nth-child(3) {
			width: 35% !important;
			font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
			font-size: 0.85em;
		}
		
		.phpinfo-content table tbody tr:hover {
			background-color: #f8f9fa !important;
		}
		
		/* Special handling for very long strings */
		.phpinfo-content table td {
			max-width: 0 !important; /* This forces the cell to respect table-layout: fixed */
			overflow: hidden;
			text-overflow: ellipsis;
		}
		
		/* Show full content on hover for long strings */
		.phpinfo-content table td:hover {
			overflow: visible !important;
			text-overflow: unset !important;
			background-color: #fff3cd !important;
			position: relative;
			z-index: 10;
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
			border-radius: 4px;
		}
		
		/* Style the first column (setting names) /
		.phpinfo-content table td:first-child {
			background-color: #f8f9fa;
			font-weight: 500;
			color: #495057;
			min-width: 200px;
		}
		
		/* Style enabled/disabled values /
		.phpinfo-content table td:contains('enabled'),
		.phpinfo-content table td[style*="color"] {
			font-weight: 500;
		}*/
		
		/* PHP Logo and headers styling */
		.phpinfo-content h1 {
			text-align: center;
			color: #6f42c1 !important;
			border-bottom: 3px solid #6f42c1 !important;
			padding-bottom: 1rem;
			margin-bottom: 2rem !important;
		}
		
		.phpinfo-content h2 {
			color: #495057 !important;
			background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
			padding: 0.75rem 1rem;
			border-left: 4px solid #007bff;
			border-radius: 0.375rem;
			margin-top: 2rem !important;
			margin-bottom: 1rem !important;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}
		
		.phpinfo-content img {
			border-radius: 0.5rem;
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
			max-height: 100px;
		}
		
		/* Center content styling */
		.phpinfo-content .text-center {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 2rem;
			border-radius: 0.5rem;
			margin-bottom: 2rem;
		}
		
		/* Pre and code blocks with better wrapping */
		.phpinfo-content pre {
			background-color: #f8f9fa !important;
			border: 1px solid #dee2e6 !important;
			border-radius: 0.375rem !important;
			padding: 1rem !important;
			font-size: 0.875rem;
			overflow-x: auto;
			white-space: pre-wrap !important;
			word-wrap: break-word !important;
			word-break: break-all !important;
			overflow-wrap: break-word !important;
		}
		
		/* Responsive adjustments */
		@media (max-width: 768px) {
			.phpinfo-content table {
				font-size: 0.875rem;
			}
			
			.phpinfo-content table th,
			.phpinfo-content table td {
				padding: 0.5rem !important;
			}
			
			.phpinfo-content h1 {
				font-size: 1.5rem !important;
			}
			
			.phpinfo-content h2 {
				font-size: 1.25rem !important;
			}
		}
		
		/* Custom badges for common values */
		.badge {
			word-break: normal !important; /* Don't break badge text */
			white-space: nowrap !important;
		}
		
		/* Custom badges for common values */
		.phpinfo-content table td:nth-child(2) {
			font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
		}
		
		/* Add some visual indicators for important settings */
		.phpinfo-content table tr:has(td:contains("memory_limit")),
		.phpinfo-content table tr:has(td:contains("max_execution_time")),
		.phpinfo-content table tr:has(td:contains("upload_max_filesize")),
		.phpinfo-content table tr:has(td:contains("post_max_size")) {
			background-color: #fff3cd !important;
			border-left: 4px solid #ffc107 !important;
		}
		
		/* Highlight security-related settings */
		.phpinfo-content table tr:has(td:contains("display_errors")),
		.phpinfo-content table tr:has(td:contains("expose_php")),
		.phpinfo-content table tr:has(td:contains("allow_url_include")) {
			background-color: #f8d7da !important;
			border-left: 4px solid #dc3545 !important;
		}
	</style>
@endsection

@section('after_scripts')
	@parent
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Add badges to common values
			const cells = document.querySelectorAll('.phpinfo-content table td');
			cells.forEach(cell => {
				const text = cell.textContent.toLowerCase().trim();
				
				// Add success badge for enabled/on
				if (text === 'on' || text === 'enabled' || text === '1') {
					cell.innerHTML = '<span class="badge bg-success">' + cell.textContent + '</span>';
				}
				// Add danger badge for disabled/off
				else if (text === 'off' || text === 'disabled' || text === '0' || text === 'no value') {
					cell.innerHTML = '<span class="badge bg-danger">' + cell.textContent + '</span>';
				}
				// Add info badge for numeric values with units
				else if (text.match(/^\d+[kmgt]?$/i) && text !== '0') {
					cell.innerHTML = '<span class="badge bg-info">' + cell.textContent + '</span>';
				}
			});
			
			// Add smooth scrolling for anchor links
			document.querySelectorAll('a[href^="#"]').forEach(anchor => {
				anchor.addEventListener('click', function (e) {
					e.preventDefault();
					const target = document.querySelector(this.getAttribute('href'));
					if (target) {
						target.scrollIntoView({
							behavior: 'smooth',
							block: 'start'
						});
					}
				});
			});
		});
	</script>
@endsection
