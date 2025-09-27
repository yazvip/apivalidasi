<?php
/**
 * API Documentation
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$pageTitle = 'API Documentation';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/plugins/autoloader/prism-autoloader.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-code text-primary-600 text-2xl mr-3"></i>
                    <h1 class="text-2xl font-bold text-gray-900">API Documentation</h1>
                </div>
                <div class="flex space-x-4">
                    <a href="admin/api-tester.php" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-flask mr-2"></i> API Tester
                    </a>
                    <a href="index.php" class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <nav class="sticky top-8">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Table of Contents</h3>
                        <ul class="space-y-2">
                            <li><a href="#overview" class="text-gray-600 hover:text-primary-600 transition-colors duration-200">Overview</a></li>
                            <li><a href="#authentication" class="text-gray-600 hover:text-primary-600 transition-colors duration-200">Authentication</a></li>
                            <li><a href="#endpoints" class="text-gray-600 hover:text-primary-600 transition-colors duration-200">Endpoints</a></li>
                            <li><a href="#bank-validation" class="text-gray-600 hover:text-primary-600 transition-colors duration-200">Bank Validation</a></li>
                            <li><a href="#phone-validation" class="text-gray-600 hover:text-primary-600 transition-colors duration-200">Phone Validation</a></li>
                            <li><a href="#error-codes" class="text-gray-600 hover:text-primary-600 transition-colors duration-200">Error Codes</a></li>
                            <li><a href="#rate-limits" class="text-gray-600 hover:text-primary-600 transition-colors duration-200">Rate Limits</a></li>
                        </ul>
                    </div>
                </nav>
            </div>

            <!-- Content -->
            <div class="lg:col-span-3">
                <div class="space-y-8">
                    <!-- Overview -->
                    <section id="overview" class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Overview</h2>
                        <div class="prose max-w-none">
                            <p class="text-gray-600 mb-4">
                                The <?= SITE_NAME ?> API provides comprehensive validation services for bank accounts and phone numbers. 
                                Our RESTful API is designed to be simple, reliable, and easy to integrate.
                            </p>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <div class="flex">
                                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-blue-900">Base URL</h4>
                                        <code class="text-blue-800"><?= SITE_URL ?></code>
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Features</h3>
                            <ul class="list-disc list-inside text-gray-600 space-y-2">
                                <li>Bank account validation for Indonesian banks</li>
                                <li>Phone number validation and formatting</li>
                                <li>Real-time validation responses</li>
                                <li>Comprehensive error handling</li>
                                <li>Rate limiting and usage tracking</li>
                                <li>Detailed API documentation</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Authentication -->
                    <section id="authentication" class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Authentication</h2>
                        <div class="prose max-w-none">
                            <p class="text-gray-600 mb-4">
                                All API requests require authentication using an API key. Include your API key in the Authorization header.
                            </p>
                            
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Header Format</h3>
                            <pre class="bg-gray-100 p-4 rounded-lg"><code>Authorization: Bearer YOUR_API_KEY</code></pre>
                            
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Getting an API Key</h3>
                            <ol class="list-decimal list-inside text-gray-600 space-y-2">
                                <li>Register for an account</li>
                                <li>Log in to your dashboard</li>
                                <li>Navigate to API Keys section</li>
                                <li>Create a new API key</li>
                                <li>Copy and securely store your API key</li>
                            </ol>
                        </div>
                    </section>

                    <!-- Endpoints -->
                    <section id="endpoints" class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Endpoints</h2>
                        <div class="space-y-6">
                            <div class="border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center mb-4">
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold mr-4">POST</span>
                                    <code class="text-lg font-mono">/api/validate_bank.php</code>
                                </div>
                                <p class="text-gray-600 mb-4">Validate Indonesian bank account numbers</p>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-gray-900 mb-2">Request Body</h4>
                                    <pre class="text-sm"><code>{
  "bank_code": "002",
  "account_number": "1234567890"
}</code></pre>
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center mb-4">
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold mr-4">POST</span>
                                    <code class="text-lg font-mono">/api/validate_phone.php</code>
                                </div>
                                <p class="text-gray-600 mb-4">Validate and format Indonesian phone numbers</p>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-gray-900 mb-2">Request Body</h4>
                                    <pre class="text-sm"><code>{
  "phone_number": "081234567890"
}</code></pre>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Bank Validation -->
                    <section id="bank-validation" class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Bank Validation</h2>
                        <div class="prose max-w-none">
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Request</h3>
                            <div class="bg-gray-100 p-4 rounded-lg mb-4">
                                <pre><code>POST /api/validate_bank.php
Content-Type: application/json
Authorization: Bearer YOUR_API_KEY

{
  "bank_code": "002",
  "account_number": "1234567890"
}</code></pre>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Response</h3>
                            <div class="bg-gray-100 p-4 rounded-lg mb-4">
                                <pre><code>{
  "success": true,
  "data": {
    "bank_name": "Bank BRI",
    "account_number": "1234567890",
    "is_valid": true,
    "formatted_number": "1234-5678-90"
  },
  "message": "Bank account validation successful"
}</code></pre>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Parameters</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parameter</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">bank_code</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">string</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">3-digit bank code (e.g., "002" for BRI)</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">account_number</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">string</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">Bank account number to validate</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- Phone Validation -->
                    <section id="phone-validation" class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Phone Validation</h2>
                        <div class="prose max-w-none">
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Request</h3>
                            <div class="bg-gray-100 p-4 rounded-lg mb-4">
                                <pre><code>POST /api/validate_phone.php
Content-Type: application/json
Authorization: Bearer YOUR_API_KEY

{
  "phone_number": "081234567890"
}</code></pre>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Response</h3>
                            <div class="bg-gray-100 p-4 rounded-lg mb-4">
                                <pre><code>{
  "success": true,
  "data": {
    "original_number": "081234567890",
    "formatted_number": "+6281234567890",
    "is_valid": true,
    "operator": "Telkomsel",
    "region": "Jakarta"
  },
  "message": "Phone number validation successful"
}</code></pre>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Parameters</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parameter</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">phone_number</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">string</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yes</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">Phone number to validate (supports 08xx or +62xx format)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- Error Codes -->
                    <section id="error-codes" class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Error Codes</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">400</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Bad Request</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">Invalid request parameters</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">401</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Unauthorized</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">Invalid or missing API key</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">403</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Forbidden</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">API key is inactive or expired</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">429</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Too Many Requests</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">Rate limit exceeded</td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">500</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Internal Server Error</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">Server error occurred</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Rate Limits -->
                    <section id="rate-limits" class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Rate Limits</h2>
                        <div class="prose max-w-none">
                            <p class="text-gray-600 mb-4">
                                API requests are limited to prevent abuse and ensure fair usage. Rate limits are applied per API key.
                            </p>
                            
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                <div class="flex">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-yellow-900">Rate Limit Information</h4>
                                        <ul class="text-yellow-800 mt-2 space-y-1">
                                            <li>• Default: 60 requests per minute</li>
                                            <li>• Daily limit: 10,000 requests per day</li>
                                            <li>• Limits reset at midnight UTC</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Rate Limit Headers</h3>
                            <p class="text-gray-600 mb-4">Every API response includes rate limit information in the headers:</p>
                            <div class="bg-gray-100 p-4 rounded-lg">
                                <pre><code>X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200</code></pre>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                <p class="text-gray-400 mt-2">API Documentation v1.0</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
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
    </script>
</body>
</html>
