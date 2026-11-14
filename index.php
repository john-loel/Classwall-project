
<?php
session_start();

require_once "db.php";

// Count visits
$_SESSION['visits'] = ($_SESSION['visits'] ?? 0) + 1;

// Get remembered name
$rememberedName = $_COOKIE['remembered_name'] ?? "";
$error = "";

// Save message
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $author = trim($_POST['author'] ?? "");
    $body = trim($_POST['body'] ?? "");
    $remember = isset($_POST['remember']);

    if ($author !== "" && $body !== "") {

        $sql = "INSERT INTO messages (author, body)
                VALUES (:author, :body)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':author' => $author,
            ':body' => $body
        ]);

        if ($remember) {
            setcookie("remembered_name", $author, time() + (30 * 86400), "/");
        } else {
            setcookie("remembered_name", "", time() - 3600, "/");
        }

        header("Location: index.php");
        exit;

    } else {
        $error = "Please enter your name and message.";
    }
}

// Search messages
$search = trim($_GET['search'] ?? "");

if ($search !== "") {

    $stmt = $pdo->prepare(
        "SELECT * FROM messages
         WHERE author ILIKE :search
         OR body ILIKE :search
         ORDER BY created_at DESC"
    );

    $stmt->execute([
        ':search' => "%" . $search . "%"
    ]);

} else {

    $stmt = $pdo->query(
        "SELECT * FROM messages ORDER BY created_at DESC"
    );
}

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Escape output
function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Message Board</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 30px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h1 {
            text-align: center;
            color: #2563eb;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        textarea {
            height: 100px;
        }

        button {
            background: #2563eb;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .message {
            background: #f4f6f9;
            padding: 15px;
            margin-top: 15px;
            border-radius: 7px;
        }

        .date {
            color: #777;
            font-size: 12px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember input {
            width: auto;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Class Message Board</h1>

    <p>
        You've visited
        <strong><?php echo $_SESSION['visits']; ?></strong>
        times during this session.
    </p>

    <h2>Post a Message</h2>

    <?php if ($error !== ""): ?>
        <p class="error"><?php echo escape($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="index.php">

        <label>Your Name:</label>

        <input
            type="text"
            name="author"
            placeholder="Enter your name"
            value="<?php echo escape($rememberedName); ?>"
            maxlength="100"
            required
        >

        <label>Your Message:</label>

        <textarea
            name="body"
            placeholder="Write your message..."
            required
        ></textarea>

        <label class="remember">
            <input type="checkbox" name="remember">
            Remember my name
        </label>

        <button type="submit">Post Message</button>

    </form>

    <hr>

    <h2>Search Messages</h2>

    <form method="GET" action="index.php">

        <input
            type="text"
            name="search"
            placeholder="Search messages..."
            value="<?php echo escape($search); ?>"
        >

        <button type="submit">Search</button>

        <a href="index.php">Show All</a>

    </form>

    <h2>Messages</h2>

    <?php if (count($messages) > 0): ?>

        <?php foreach ($messages as $message): ?>

            <div class="message">

                <h3><?php echo escape($message['author']); ?></h3>

                <p><?php echo nl2br(escape($message['body'])); ?></p>

                <p class="date">
                    <?php echo escape($message['created_at']); ?>
                </p>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p>No messages found.</p>

    <?php endif; ?>

</div>

</body>
</html>
