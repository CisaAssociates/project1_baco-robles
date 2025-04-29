<?php
include 'conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 p-6">
  <h1 class="text-4xl font-bold mb-10 text-center text-gray-800">Welcome to the System Dashboard</h1>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto mt-20">
    <!-- Register Card -->
    <a href="register.php" class="block h-64 w-full rounded-2xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-1 bg-white hover:bg-blue-50 p-6">
      <div class="flex flex-col justify-between h-full items-center gap-4 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m8-4a4 4 0 118 0m6 4v-2a4 4 0 00-3-3.87" />
        </svg>
        <h2 class="text-2xl font-semibold text-blue-800">Register</h2>
        <p class="text-sm text-gray-600">Create and manage user profiles.</p>
      </div>
    </a>

    <!-- Attendance Logs Card -->
    <a href="attendance_logs.php" class="block h-64 w-full rounded-2xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-1 bg-white hover:bg-green-50 p-6">
      <div class="flex flex-col justify-between h-full items-center gap-4 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.104.896-2 2-2s2 .896 2 2-2 2-2 2-2-.896-2-2zm0 4v1m0-5V9m0-1.5A6.5 6.5 0 1122 14h-1.5" />
        </svg>
        <h2 class="text-2xl font-semibold text-green-800">Attendance Logs</h2>
        <p class="text-sm text-gray-600">View and manage attendance records.</p>
      </div>
    </a>

    <!-- Gatepass Logs Card -->
    <a href="gatepass_logs.php" class="block h-64 w-full rounded-2xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-1 bg-white hover:bg-yellow-50 p-6">
      <div class="flex flex-col justify-between h-full items-center gap-4 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m2 4H7m4-8h2m2-2v.01M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
        </svg>
        <h2 class="text-2xl font-semibold text-yellow-700">Gatepass Logs</h2>
        <p class="text-sm text-gray-600">Access gatepass request history.</p>
      </div>
    </a>
  </div>
</body>
</html>
