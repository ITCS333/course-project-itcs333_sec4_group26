<?php
/**
 * Homepage - ITCS333 Course Page
 * Public landing page with course information
 */

require_once 'config.php';

$isLoggedIn = isLoggedIn();
$isAdminUser = isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITCS333 - Internet Software Development</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        nav {
            background-color: #1a1a1a;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-right: 1rem;
        }
        .hero {
            text-align: center;
            padding: 3rem 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .hero h1 {
            color: white;
            margin-bottom: 1rem;
        }
        .course-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .info-card h3 {
            margin-top: 0;
            color: #667eea;
        }
        .user-info {
            color: #ccc;
        }
        footer {
            text-align: center;
            padding: 2rem 0;
            margin-top: 3rem;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <nav>
        <div class="container">
            <div>
                <a href="homepage.php"><strong>ITCS333</strong></a>
                <?php if ($isAdminUser): ?>
                    <a href="admin_portal.php">Admin Portal</a>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <?php if ($isLoggedIn): ?>
                    Welcome, <?= htmlspecialchars($_SESSION['name']) ?> | 
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="hero">
            <h1>ITCS333: Internet Software Development</h1>
            <p>Building Modern Web Applications</p>
        </div>

        <?php if ($isLoggedIn): ?>
            <article>
                <h2>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>!</h2>
                <p>You are logged in as: <strong><?= ucfirst($_SESSION['role']) ?></strong></p>
                
                <?php if ($isAdminUser): ?>
                    <p>
                        <a href="admin_portal.php" role="button">Go to Admin Portal</a>
                    </p>
                <?php endif; ?>
            </article>
        <?php else: ?>
            <article>
                <h2>Welcome to ITCS333</h2>
                <p>Please <a href="login.php">login</a> to access course materials and features.</p>
            </article>
        <?php endif; ?>

        <section>
            <h2>Course Information</h2>
            <div class="course-info">
                <div class="info-card">
                    <h3>📚 Course Code</h3>
                    <p>ITCS333</p>
                </div>
                <div class="info-card">
                    <h3>💻 Topics Covered</h3>
                    <p>HTML, CSS, JavaScript, PHP, MySQL, Web Security</p>
                </div>
                <div class="info-card">
                    <h3>🎯 Learning Outcomes</h3>
                    <p>Full-stack web development skills</p>
                </div>
                <div class="info-card">
                    <h3>👥 Team Project</h3>
                    <p>Collaborative development using Git</p>
                </div>
            </div>
        </section>

        <section>
            <h2>About This Course</h2>
            <p>
                ITCS333 - Internet Software Development is a comprehensive course that teaches you how to build 
                modern, dynamic web applications from scratch. You'll learn both front-end and back-end development, 
                database design, and how to deploy your applications.
            </p>
            
            <h3>What You'll Learn</h3>
            <ul>
                <li>Design and structure multi-page websites using HTML and CSS</li>
                <li>Enhance user interfaces with client-side JavaScript</li>
                <li>Develop server-side applications using PHP</li>
                <li>Design and interact with MySQL databases using PDO</li>
                <li>Implement full CRUD (Create, Read, Update, Delete) functionality</li>
                <li>Manage user authentication and authorization</li>
                <li>Collaborate effectively using Git and GitHub</li>
            </ul>
        </section>

        <?php if (!$isLoggedIn): ?>
        <section style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
            <h3>Ready to Get Started?</h3>
            <p>Login to access all course features and materials.</p>
            <a href="login.php" role="button">Login Now</a>
        </section>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 ITCS333 - Internet Software Development. All rights reserved.</p>
            <p><small>University of Bahrain - College of Information Technology</small></p>
        </div>
    </footer>
</body>
</html>
