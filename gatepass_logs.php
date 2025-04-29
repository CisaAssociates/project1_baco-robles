<?php
// gatepass_logs.php

require_once 'conn.php';

$sql = "SELECT gatepass_logs.entry_time, gatepass_logs.exit_time, users.name
        FROM gatepass_logs
        JOIN users ON gatepass_logs.user_id = users.id
        ORDER BY gatepass_logs.entry_time DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gatepass Logs</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-50 via-blue-50 to-yellow-50 p-6 text-gray-800">

  <div class="mb-6">
    <a href="index.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 text-white font-semibold py-2 px-5 rounded-full shadow-md transition duration-200 ease-in-out">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      Return
    </a>
  </div>

  <h2 class="text-3xl font-bold mb-10 text-center text-green-800">Gatepass Logs</h2>

  <div class="w-full bg-white rounded-xl shadow-md p-6 overflow-auto">
    <table id="gatepassTable" class="display nowrap min-w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg">
      <thead class="bg-green-100 text-green-900 font-semibold">
        <tr>
          <th class="px-4 py-2 border-b">User Name</th>
          <th class="px-4 py-2 border-b">Entry Time</th>
          <th class="px-4 py-2 border-b">Exit Time</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr class="hover:bg-green-50 transition-colors">
          <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($row['name']); ?></td>
          <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($row['entry_time']); ?></td>
          <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($row['exit_time'] ?: '—'); ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <script>
    $(document).ready(function () {
      $('#gatepassTable').DataTable({
        responsive: true,
        paging: true,
        searching: true,
        info: false
      });
    });
  </script>
</body>
</html>
