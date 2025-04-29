<?php
// attendance_logs.php

// Include database connection
require_once 'conn.php';

// Fetch attendance logs along with user names
$sql = "SELECT attendance_logs.timestamp, attendance_logs.status, users.name
        FROM attendance_logs
        JOIN users ON attendance_logs.user_id = users.id";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Logs</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 p-6 text-gray-800">

  <div class="mb-6">
    <a href="index.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 text-white font-semibold py-2 px-5 rounded-full shadow-md transition duration-200 ease-in-out">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      Return
    </a>
  </div>

  <h2 class="text-3xl font-bold mb-10 text-center text-blue-800">Attendance Logs</h2>

  <div class="w-full bg-white rounded-xl shadow-md p-6 overflow-auto">
    <table id="attendanceTable" class="display nowrap min-w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg">
      <thead class="bg-purple-100 text-purple-900 font-semibold">
        <tr>
          <th class="px-4 py-2 border-b">User Name</th>
          <th class="px-4 py-2 border-b">Timestamp</th>
          <th class="px-4 py-2 border-b">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr class="hover:bg-purple-50 transition-colors">
          <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($row['name']); ?></td>
          <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($row['timestamp']); ?></td>
          <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($row['status']); ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <script>
    $(document).ready(function () {
      $('#attendanceTable').DataTable({
        responsive: true,
        paging: true,
        searching: true,
        info: false
      });
    });
  </script>
</body>
</html>
