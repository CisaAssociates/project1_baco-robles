<?php

include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $rfid_tag = $_POST['rfid_tag'];
    $name = $_POST['name'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET name = ?, role = ? WHERE rfid_tag = ?");
    $stmt->bind_param("sss", $name, $role, $rfid_tag);
    $stmt->execute();
    $stmt->close();

    echo "<script>sessionStorage.setItem('actionIcon', 'success'); sessionStorage.setItem('actionMessage', 'User updated successfully!');</script>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $role = $_POST['role'];

    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
    $stmt_check->bind_param("s", $name);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        $message = "User already registered.";
        echo "<script>sessionStorage.setItem('actionIcon', 'warning'); sessionStorage.setItem('actionMessage', '$message');</script>";
    } else {
        $rfid_check = $conn->query("SELECT COUNT(*) FROM users WHERE rfid_tag IS NULL");
        $rfid_check_result = $rfid_check->fetch_row()[0];
        $rfid_check->close();

        if ($rfid_check_result > 0) {
            echo "<script>
                sessionStorage.setItem('actionIcon', 'warning');
                sessionStorage.setItem('actionMessage', 'Please assign RFID to the last user before adding a new one.');
                window.location.href = window.location.href;
            </script>";
            exit;
        }

        $face_data = null;
        if (isset($_FILES['face_data']) && $_FILES['face_data']['error'] == 0) {
            $face_data = file_get_contents($_FILES['face_data']['tmp_name']);
        }

        $stmt = $conn->prepare("INSERT INTO users (rfid_tag, name, face_data, role) VALUES (NULL, ?, ?, ?)");
        $stmt->bind_param("sbs", $name, $face_data, $role);
        $stmt->send_long_data(1, $face_data);

        if ($stmt->execute()) {
            $message = "User added successfully! Now scan their RFID tag.";
            echo "<script>sessionStorage.setItem('actionIcon', 'success'); sessionStorage.setItem('actionMessage', '$message');</script>";
        } else {
            $message = "Error: " . $stmt->error;
            echo "<script>sessionStorage.setItem('actionIcon', 'warning'); sessionStorage.setItem('actionMessage', '$message');</script>";
        }

        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $rfid_tag = $_POST['rfid_tag'];
    $stmt = $conn->prepare("DELETE FROM users WHERE rfid_tag = ?");
    $stmt->bind_param("s", $rfid_tag);
    $stmt->execute();
    $stmt->close();

    echo "<script>sessionStorage.setItem('actionIcon', 'success'); sessionStorage.setItem('actionMessage', 'User deleted successfully!');</script>";
}

$users_result = $conn->query("SELECT rfid_tag, name, role, CASE WHEN rfid_tag IS NULL THEN 1 ELSE 0 END AS is_missing_rfid FROM users");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Register User</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 p-6 text-gray-800">

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const icon = sessionStorage.getItem("actionIcon");
            const msg = sessionStorage.getItem("actionMessage");
            if (msg) {
                Swal.fire({
                    icon: icon,
                    text: msg,
                    timer: 2500,
                    showConfirmButton: false
                });
                sessionStorage.removeItem("actionMessage");
            }
        });
    </script>

    <div class="mb-6">
        <a href="index.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 text-white font-semibold py-2 px-5 rounded-full shadow-md transition duration-200 ease-in-out">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Return
        </a>
    </div>

    <h2 class="text-3xl font-bold mb-10 text-center text-blue-800">User Registration & List</h2>

    <div class="flex flex-col lg:flex-row gap-10 justify-center max-w-7xl mx-auto">
        <div class="w-full lg:w-1/3 bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4 text-blue-700">Add New User</h3>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="add_user" value="1">
                <div>
                    <label class="block font-semibold mb-1">Name:</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-300">
                </div>

                <div>
                    <label class="block font-semibold mb-1">Upload Face Image:</label>
                    <input type="file" name="face_data" accept="image/*" required class="w-full px-4 py-2 border rounded-lg">
                </div>

                <div>
                    <label class="block font-semibold mb-1">Role:</label>
                    <select name="role" required class="w-full px-4 py-2 border rounded-lg">
                        <option value="Student">Student</option>
                        <option value="Staff">Staff</option>
                        <option value="Visitor">Visitor</option>
                    </select>
                </div>

                <div>
                    <input type="submit" value="Add User" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                </div>
            </form>
        </div>

        <div class="w-full lg:w-2/3 bg-white rounded-xl shadow-md p-6 overflow-auto">
            <h3 class="text-xl font-semibold mb-4 text-blue-700">Registered Users</h3>
            <table id="userTable" class="display nowrap min-w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg">
                <thead class="bg-blue-100 text-blue-900 font-semibold">
                    <tr>
                        <th class="px-4 py-2 border-b">RFID Tag</th>
                        <th class="px-4 py-2 border-b">Name</th>
                        <th class="px-4 py-2 border-b">Role</th>
                        <th class="px-4 py-2 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $users_result->fetch_assoc()): ?>
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($row['rfid_tag']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($row['role']); ?></td>
                            <td class="px-4 py-2 border-b">
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <button onclick="openEditModal('<?php echo $row['rfid_tag']; ?>', '<?php echo addslashes($row['name']); ?>', '<?php echo $row['role']; ?>')" class="flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-700 text-white text-xs font-semibold rounded-lg shadow hover:from-blue-600 hover:to-blue-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4L16.5 3.5z" />
                                        </svg>
                                        Update
                                    </button>
                                    <button onclick="confirmDelete('<?php echo $row['rfid_tag']; ?>')" class="flex items-center justify-center px-4 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-semibold rounded-lg shadow hover:from-red-600 hover:to-pink-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-3h4m-4 0a1 1 0 00-1 1v1h6V5a1 1 0 00-1-1m-4 0h4" />
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="delete_user" value="1">
        <input type="hidden" name="rfid_tag" id="delete_rfid">
    </form>

    <div id="editModal" class="fixed inset-0 hidden bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Edit User</h3>
            <form method="POST">
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" name="rfid_tag" id="edit_rfid">
                <div class="mb-4">
                    <label class="block mb-1 font-medium">Name</label>
                    <input type="text" name="name" id="edit_name" class="w-full border px-3 py-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium">Role</label>
                    <select name="role" id="edit_role" class="w-full border px-3 py-2 rounded">
                        <option value="Student">Student</option>
                        <option value="Staff">Staff</option>
                        <option value="Visitor">Visitor</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeEditModal()" class="mr-3 px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(rfid, name, role) {
            $('#edit_rfid').val(rfid);
            $('#edit_name').val(name);
            $('#edit_role').val(role);
            $('#editModal').removeClass('hidden');
        }

        function closeEditModal() {
            $('#editModal').addClass('hidden');
        }

        function confirmDelete(rfid) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You will not be able to recover this user!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#delete_rfid').val(rfid);
                    $('#deleteForm').submit();
                }
            });
        }

        $(document).ready(function() {
            $('#userTable').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                info: false
            });
        });
    </script>
</body>

</html>
