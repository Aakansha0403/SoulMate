<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];

// Automatically build Community Engine tables securely
$conn->query("CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS post_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('love', 'sad', 'heart') NOT NULL,
    UNIQUE KEY user_post_reaction (post_id, user_id, reaction_type),
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$msg = "";

// Handle New Post Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']);
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO community_posts (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $content);
        if ($stmt->execute()) {
            $msg = "<div class='success-msg'>✓ Your thought has been securely shared with the community.</div>";
        }
    }
}

// Handle Reaction Click via pure PHP redirect processing (for high speed safety)
if (isset($_GET['action']) && $_GET['action'] == 'react' && isset($_GET['post_id']) && isset($_GET['type'])) {
    $p_id = (int)$_GET['post_id'];
    $type = $_GET['type'];
    if (in_array($type, ['love', 'sad', 'heart'])) {
        // Toggle Reaction State natively
        $stmt = $conn->prepare("SELECT id FROM post_reactions WHERE post_id=? AND user_id=? AND reaction_type=?");
        $stmt->bind_param("iis", $p_id, $user_id, $type);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $del = $conn->prepare("DELETE FROM post_reactions WHERE post_id=? AND user_id=? AND reaction_type=?");
            $del->bind_param("iis", $p_id, $user_id, $type);
            $del->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
            $ins->bind_param("iis", $p_id, $user_id, $type);
            $ins->execute();
        }
        header("Location: community.php");
        exit();
    }
}

// Memory Fetch: Pull highly optimized Community Feed
$posts = [];
$query = "
    SELECT p.id, p.content, p.created_at, u.name,
        (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id AND reaction_type = 'heart') as hearts,
        (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id AND reaction_type = 'sad') as sads,
        (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id AND reaction_type = 'love') as loves,
        MAX(CASE WHEN pr.user_id = ? AND pr.reaction_type = 'heart' THEN 1 ELSE 0 END) as user_hearted,
        MAX(CASE WHEN pr.user_id = ? AND pr.reaction_type = 'sad' THEN 1 ELSE 0 END) as user_sadded,
        MAX(CASE WHEN pr.user_id = ? AND pr.reaction_type = 'love' THEN 1 ELSE 0 END) as user_loved
    FROM community_posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN post_reactions pr ON p.id = pr.post_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 50
";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $posts[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - Community</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { 
            background: linear-gradient(135deg, #090614, #191238, #000000); 
            color: white; 
            font-family: 'Poppins', sans-serif; 
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
        }

        .hub-container {
            width: 100%;
            max-width: 700px;
            padding: 30px 20px 100px 20px; /* Padding bottom for player */
            display: flex;
            flex-direction: column;
            gap: 30px;
            animation: fadeIn 1.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .header-title {
            text-align: center;
            font-size: 2.5rem;
            margin: 0;
            background: linear-gradient(to right, #ff7eb3, #d475ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .glass-box {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s;
        }
        
        .textarea-input {
            width: 100%;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 15px;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            min-height: 120px;
            resize: none;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s;
        }
        .textarea-input:focus { border-color: #ff7eb3; box-shadow: 0 0 15px rgba(255,126,179,0.2); }

        .btn-submit {
            background: linear-gradient(45deg, #d475ff, #8a2be2);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 20px;
            font-weight: 600;
            font-family: inherit;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 15px;
            float: right;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(138, 43, 226, 0.4);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(138, 43, 226, 0.6); }

        .success-msg { color: #ff7eb3; margin-bottom: 20px; text-shadow: 0 0 10px rgba(255,126,179,0.4); }
        .clearfix::after { content: ""; clear: both; display: table; }

        /* Feed List Style Structure */
        .post-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 18px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .post-header { display: flex; justify-content: space-between; align-items: center; }
        .post-author { font-weight: 600; color: #a18cd1; font-size: 1.1rem; }
        .post-date { font-size: 0.8rem; color: rgba(255,255,255,0.4); letter-spacing: 1px; }
        .post-body { font-size: 1.05rem; line-height: 1.6; color: rgba(255,255,255,0.85); white-space: pre-wrap; word-wrap: break-word;}

        /* Reaction Engine */
        .reaction-bar { display: flex; gap: 15px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 15px; margin-top: 5px; }
        .re-btn {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px;
            padding: 6px 15px;
            color: rgba(255,255,255,0.6);
            cursor: pointer;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .re-btn:hover { background: rgba(255,255,255,0.1); color: white; border-color: rgba(255,255,255,0.3);}
        .re-active-heart { background: rgba(255,71,87,0.15); border-color: #ff4757; color: #ff4757; }
        .re-active-sad { background: rgba(84,160,255,0.15); border-color: #54a0ff; color: #54a0ff; }
        .re-active-love { background: rgba(255,159,67,0.15); border-color: #ff9f43; color: #ff9f43; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    
    <div class="hub-container">
        <h1 class="header-title">Soul Community</h1>
        
        <div class="glass-box clearfix">
            <?php echo $msg; ?>
            <form action="community.php" method="POST">
                <textarea name="post_content" class="textarea-input" placeholder="Share a poem, shayri, or just empty your feelings out anonymously..." required></textarea>
                <button type="submit" class="btn-submit">Echo to the world</button>
            </form>
        </div>

        <!-- Render Post Feed -->
        <div style="display:flex; flex-direction:column; gap:20px;">
            <?php if(empty($posts)): ?>
                <div style="text-align:center; opacity:0.5; font-style:italic; padding:40px;">No souls have spoken yet. Be the first.</div>
            <?php else: ?>
                <?php foreach($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <span class="post-author">@<?php echo htmlspecialchars($post['name']); ?></span>
                            <span class="post-date"><?php echo date('M j, g:i a', strtotime($post['created_at'])); ?></span>
                        </div>
                        <div class="post-body"><?php echo htmlspecialchars($post['content']); ?></div>
                        
                        <div class="reaction-bar">
                            <a href="community.php?action=react&type=heart&post_id=<?php echo $post['id']; ?>" class="re-btn <?php echo $post['user_hearted'] ? 're-active-heart' : ''; ?>">
                                ❤️ <?php echo $post['hearts']; ?>
                            </a>
                            <a href="community.php?action=react&type=sad&post_id=<?php echo $post['id']; ?>" class="re-btn <?php echo $post['user_sadded'] ? 're-active-sad' : ''; ?>">
                                😢 <?php echo $post['sads']; ?>
                            </a>
                            <a href="community.php?action=react&type=love&post_id=<?php echo $post['id']; ?>" class="re-btn <?php echo $post['user_loved'] ? 're-active-love' : ''; ?>">
                                😍 <?php echo $post['loves']; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/global_player.php'; ?>
</body>
</html>
