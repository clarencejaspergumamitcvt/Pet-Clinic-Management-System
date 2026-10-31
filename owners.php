<?php
session_start();
require_once 'db.php';

// Protection: Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$currentUserId = $_SESSION['user_id'];

// 1. Handle Insert Owner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_owner') {
    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $phone     = trim($_POST['phone']);
    $address   = trim($_POST['address']);

    $stmt = $pdo->prepare("INSERT INTO owners (first_name, last_name, phone, address, added_by, last_updated) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$firstName, $lastName, $phone, $address, $currentUserId, $currentUserId]);
    header("Location: owners.php");
    exit;
}

// 2. Handle Update Owner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_owner') {
    $ownerId   = $_POST['owner_id'];
    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $phone     = trim($_POST['phone']);
    $address   = trim($_POST['address']);

    $stmt = $pdo->prepare("UPDATE owners SET first_name = ?, last_name = ?, phone = ?, address = ?, last_updated = ? WHERE id = ?");
    $stmt->execute([$firstName, $lastName, $phone, $address, $currentUserId, $ownerId]);
    header("Location: owners.php");
    exit;
}

// 3. Handle Delete Owner
if (isset($_GET['delete_owner'])) {
    $ownerId = $_GET['delete_owner'];
    $stmt = $pdo->prepare("DELETE FROM owners WHERE id = ?");
    $stmt->execute([$ownerId]);
    header("Location: owners.php");
    exit;
}

// Fetch all owners joined with user audit details
$sql = "SELECT o.*, 
               u1.username AS creator, 
               u2.username AS modifier 
        FROM owners o
        JOIN users u1 ON o.added_by = u1.id
        JOIN users u2 ON o.last_updated = u2.id
        ORDER BY o.updated_at DESC";
$owners = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Owners (Parent Entity) - Pet Clinic</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef2f5; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .nav { margin-bottom: 15px; }
        .nav a { margin-right: 15px; font-weight: bold; text-decoration: none; color: #007bff; }
        .container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #28a745; color: white; }
        .btn-logout { background: #dc3545; color: white; text-decoration: none; padding: 8px 12px; border-radius: 4px; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .btn-delete { color: #dc3545; text-decoration: none; font-weight: bold; }
        input { padding: 6px; margin-right: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Parent Entity Management: Pet Owners</h2>
        <div>
            Logged in as: <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong> | 
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="nav">
        <a href="owners.php">Manage Owners (Parent)</a> | 
        <a href="index.php">Manage Pets (Child)</a>
    </div>

    <!-- Add Owner Form -->
    <div class="container">
        <h3>Add New Owner</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_owner">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="text" name="phone" placeholder="Phone Number" required>
            <input type="text" name="address" placeholder="Address">
            <button type="submit" class="btn-submit">Add Owner</button>
        </form>
    </div>

    <!-- Display Owners Table -->
    <div class="container">
        <h3>Owners List with Audit Tracking</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Added By</th>
                    <th>Last Updated By</th>
                    <th>Last Timestamp</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($owners as $owner): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_owner">
                        <input type="hidden" name="owner_id" value="<?= $owner['id'] ?>">
                        <td><?= $owner['id'] ?></td>
                        <td><input type="text" name="first_name" value="<?= htmlspecialchars($owner['first_name']) ?>" style="width: 100px;" required></td>
                        <td><input type="text" name="last_name" value="<?= htmlspecialchars($owner['last_name']) ?>" style="width: 100px;" required></td>
                        <td><input type="text" name="phone" value="<?= htmlspecialchars($owner['phone']) ?>" style="width: 110px;" required></td>
                        <td><input type="text" name="address" value="<?= htmlspecialchars($owner['address']) ?>" style="width: 150px;"></td>
                        <td><strong><?= htmlspecialchars($owner['creator']) ?></strong></td>
                        <td><strong><?= htmlspecialchars($owner['modifier']) ?></strong></td>
                        <td><?= $owner['updated_at'] ?></td>
                        <td>
                            <button type="submit" class="btn-submit">Save</button>
                            <a href="owners.php?delete_owner=<?= $owner['id'] ?>" class="btn-delete" onclick="return confirm('Delete this owner record?')">Delete</a>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
