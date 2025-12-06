<?php
require_once '../config/database.php';
require_once '../config/session.php';

$pdo = getDBConnection();
$stmt = $pdo->query("SELECT r.*, u.name as creator_name, (SELECT COUNT(*) FROM resource_comments WHERE resource_id = r.id) as comment_count FROM resources r LEFT JOIN users u ON r.created_by = u.id ORDER BY r.created_at DESC");
$resources = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Resources - ITCS333</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .resource-card {
            margin-bottom: 1rem;
            padding: 1rem;
            border: 1px solid var(--muted-border-color);
            border-radius: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .resource-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .resource-type-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .badge-book_chapter { background-color: #e3f2fd; color: #1976d2; }
        .badge-lecture_notes { background-color: #f3e5f5; color: #7b1fa2; }
        .badge-web_link { background-color: #e8f5e9; color: #388e3c; }
        .badge-other { background-color: #fff3e0; color: #f57c00; }
        .stats {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--muted-color);
        }
    </style>
</head>
<body>
    <nav class="container-fluid">
        <ul>
            <li><strong>ITCS333 Course</strong></li>
        </ul>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="index.php" class="active">Resources</a></li>
            <?php if (isAdmin()) { ?>
                <li><a href="../admin/resources.php">Manage Resources</a></li>
            <?php } ?>
            <li><a href="../logout.php">Logout (<?= getCurrentUserName() ?>)</a></li>
        </ul>
    </nav>

    <main class="container">
        <header>
            <h1>Course Resources</h1>
            <p>Browse and explore course materials, lecture notes, and helpful resources.</p>
        </header>

        <?php if (empty($resources)) { ?>
            <article>
                <p>No resources available yet. Check back later!</p>
            </article>
        <?php } else { ?>
            <!-- Filter/Search Section -->
            <article>
                <div class="grid">
                    <div>
                        <label for="search">
                            Search Resources
                            <input type="text" id="search" placeholder="Type to search..." onkeyup="filterResources()">
                        </label>
                    </div>
                    <div>
                        <label for="typeFilter">
                            Filter by Type
                            <select id="typeFilter" onchange="filterResources()">
                                <option value="">All Types</option>
                                <option value="book_chapter">Book Chapters</option>
                                <option value="lecture_notes">Lecture Notes</option>
                                <option value="web_link">Web Links</option>
                                <option value="other">Other</option>
                            </select>
                        </label>
                    </div>
                </div>
            </article>

            <!-- Resources Grid -->
            <div id="resourcesContainer">
                <?php foreach ($resources as $resource) { ?>
                    <article class="resource-card" 
                             data-type="<?= $resource['resource_type'] ?>"
                             data-title="<?= strtolower($resource['title']) ?>"
                             data-description="<?= strtolower($resource['description']) ?>">
                        <span class="resource-type-badge badge-<?= $resource['resource_type'] ?>">
                            <?= ucwords(str_replace('_', ' ', $resource['resource_type'])) ?>
                        </span>
                        
                        <h3>
                            <a href="view.php?id=<?= $resource['id'] ?>">
                                <?= $resource['title'] ?>
                            </a>
                        </h3>
                        
                        <p><?= $resource['description'] ?></p>
                        
                        <?php if ($resource['resource_url']) { ?>
                            <p>
                                <a href="<?= $resource['resource_url'] ?>" target="_blank" rel="noopener">
                                    Access Resource
                                </a>
                            </p>
                        <?php } ?>
                        
                        <div class="stats">
                            <span><?= $resource['comment_count'] ?> comment(s)</span>
                            <span><?= date('M d, Y', strtotime($resource['created_at'])) ?></span>
                            <span>By: <?= $resource['creator_name'] ?></span>
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <a href="view.php?id=<?= $resource['id'] ?>" role="button">View Details & Discuss</a>
                        </div>
                    </article>
                <?php } ?>
            </div>

            <div id="noResults" style="display: none;">
                <article>
                    <p>No resources found matching your criteria.</p>
                </article>
            </div>
        <?php } ?>
    </main>

    <footer class="container">
        <small>ITCS333 - Internet Software Development | Total Resources: <?= count($resources) ?></small>
    </footer>

    <script>
        function filterResources() {
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            const cards = document.querySelectorAll('.resource-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const title = card.getAttribute('data-title');
                const description = card.getAttribute('data-description');
                const type = card.getAttribute('data-type');
                
                const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
                const matchesType = !typeFilter || type === typeFilter;
                
                if (matchesSearch && matchesType) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
        }
    </script>
</body>
</html>
