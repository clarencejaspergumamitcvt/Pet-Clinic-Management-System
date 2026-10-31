<?php
session_start();
require_once 'db.php';

// Protection: Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$currentUserId = $_SESSION['user_id'];

// Handle Creation (Insert Pet)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_pet') {
    $ownerId = $_POST['owner_id'];
    $petName = $_POST['pet_name'];
    $species = $_POST['species'];
    $breed   = $_POST['breed'];
    $age     = $_POST['age'];

    $stmt = $pdo->prepare("INSERT INTO pets (owner_id, pet_name, species, breed, age, added_by, last_updated) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$ownerId, $petName, $species, $breed, $age, $currentUserId, $currentUserId]);
    header("Location: index.php");
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pet') {
    $petId   = $_POST['pet_id'];
    $petName = $_POST['pet_name'];
    $species = $_POST['species'];
    $breed   = $_POST['breed'];
    $age     = $_POST['age'];

    $stmt = $pdo->prepare("UPDATE pets SET pet_name = ?, species = ?, breed = ?, age = ?, last_updated = ? WHERE id = ?");
    $stmt->execute([$petName, $species, $breed, $age, $currentUserId, $petId]);
    header("Location: index.php");
    exit;
}

// Handle Deletion
if (isset($_GET['delete_pet'])) {
    $petId = $_GET['delete_pet'];
    $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ?");
    $stmt->execute([$petId]);
    header("Location: index.php");
    exit;
}

// Fetch Records with Join for User Tracking
$sql = "SELECT p.*, 
               CONCAT(o.first_name, ' ', o.last_name) AS owner_name,
               u1.username AS creator,
               u2.username AS modifier
        FROM pets p
        JOIN owners o ON p.owner_id = o.id
        JOIN users u1 ON p.added_by = u1.id
        JOIN users u2 ON p.last_updated = u2.id
        ORDER BY p.updated_at DESC";
$pets = $pdo->query($sql)->fetchAll();

// Fetch Owners for Dropdown Selection
$owners = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM owners")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Pet Clinic</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef2f5; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007bff; color: white; }
        .btn-logout { background: #dc3545; color: white; text-decoration: none; padding: 8px 12px; border-radius: 4px; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .btn-delete { color: #dc3545; text-decoration: none; }
        form.inline { display: inline; }
        input, select { padding: 6px; margin-right: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Pet Clinic Management</h2>
        <div>
            Welcome, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong> | 
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Add Pet Form -->
    <div class="container">
        <h3>Add New Pet</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_pet">
            <select name="owner_id" required>
                <option value="">Select Owner</option>
                <?php foreach ($owners as $owner): ?>
                    <option value="<?= $owner['id'] ?>"><?= htmlspecialchars($owner['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="pet_name" placeholder="Pet Name" required>
            <input type="text" name="species" placeholder="Species (e.g. Dog)" required>
            <input type="text" name="breed" placeholder="Breed">
            <input type="number" name="age" placeholder="Age" style="width: 60px;">
            <button type="submit" class="btn-submit">Add Record</button>
        </form>
    </div>

    <!-- Pets Table with Audit Tracking -->
    <div class="container">
        <h3>Registered Pets & Audit Logs</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pet Name</th>
                    <th>Owner</th>
                    <th>Species/Breed</th>
                    <th>Age</th>
                    <th>Added By</th>
                    <th>Last Updated By</th>
                    <th>Last Updated Timestamp</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pets as $pet): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_pet">
                        <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                        <td><?= $pet['id'] ?></td>
                        <td><input type="text" name="pet_name" value="<?= htmlspecialchars($pet['pet_name']) ?>" required></td>
                        <td><?= htmlspecialchars($pet['owner_name']) ?></td>
                        <td>
                            <input type="text" name="species" value="<?= htmlspecialchars($pet['species']) ?>" style="width: 80px;" required>
                            <input type="text" name="breed" value="<?= htmlspecialchars($pet['breed']) ?>" style="width: 100px;">
                        </td>
                        <td><input type="number" name="age" value="<?= $pet['age'] ?>" style="width: 50px;"></td>
                        <td><strong><?= htmlspecialchars($pet['creator']) ?></strong></td>
                        <td><strong><?= htmlspecialchars($pet['modifier']) ?></strong></td>
                        <td><?= $pet['updated_at'] ?></td>
                        <td>
                            <button type="submit" class="btn-submit">Save</button>
                            <a href="index.php?delete_pet=<?= $pet['id'] ?>" class="btn-delete" onclick="return confirm('Delete this record?')">Delete</a>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
