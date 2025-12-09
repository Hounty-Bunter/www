<?php
session_start();
require_once 'mysql.php'; // Include the database connection file

$userId = $_SESSION['user_id'] ?? null;
$isLoggedIn = $_SESSION['logged_in'] ?? false;

// Check if the form is submitted to post a tweet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tweet_content'])) {
    $tweetContent = trim($_POST['tweet_content']);

    // Check if tweet content is not empty
    if (!empty($tweetContent)) {
        // Require authenticated user
        if ($userId && $isLoggedIn) {
            // Prepare and execute the insert query
            $stmt = $conn->prepare("INSERT INTO tweets (user_id, content, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("is", $userId, $tweetContent);
            $stmt->execute();
            $stmt->close();

            // Redirect to avoid form resubmission on refresh
            header('Location: index.php');
            exit();
        } else {
            header('Location: msg.php?msg=' . rawurlencode('Please log in to post a tweet.') . '&type=error&goto=login.php');
            exit();
        }
    } else {
        echo "<p>Please enter some content for your tweet.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hounty Bunter - Home</title>
    <link rel="stylesheet" href="https://static.hountybunter.click/styles.css?v=3">

</head>
<body>

<!-- Actions --> <div class="home-actions">
     <a class="btn primary" href="register.php">Create account</a> 
     <a class="btn ghost" href="login.php">Log in</a>
      <a class="btn ghost" href="all_users.php">Go to users</a> 
    </div> 
    <div class="home-container">
        <?php if ($isLoggedIn && $userId): ?>
            <div class="tweet-form">
                <h2>Post a Tweet</h2>
                <form id="tweet-form">
                    <textarea id="tweet_content" name="tweet_content" placeholder="What's happening?" rows="4" required></textarea>
                    <div class="post-btn-container">
                        <button id="tweet_submit" type="submit" class="btn primary">Post Tweet</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="tweets-list">
            <h2>Latest Tweets</h2>
            <div id="tweets-status"></div>
            <div id="tweets-container"></div>
        </div>
        <div class="home-footer-links">
        <a class= "btn ghost" href ="dashboard.php">Go to dashboard</a>
    </div>
    </div>

</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('message', function(event) {
        console.log('Received postMessage:', event.data);
    });

    var container = document.getElementById('tweets-container');
    var statusEl = document.getElementById('tweets-status');
    var tweetForm = document.getElementById('tweet-form');
    var tweetInput = document.getElementById('tweet_content');
    var tweetBtn = document.getElementById('tweet_submit');
    if (!container) {
        return;
    }

    function setStatus(message) {
        if (statusEl) {
            statusEl.textContent = message || '';
        }
    }

    function renderTweets(tweets) {
        if (!Array.isArray(tweets) || tweets.length === 0) {
            container.innerHTML = '<p>No tweets to display.</p>';
            return;
        }

        container.innerHTML = '';
        tweets.forEach(function(tweet) {
            var card = document.createElement('div');
            card.className = 'tweet-card';

            var img = document.createElement('img');
            img.src = tweet.profile_picture_url || 'https://static.hountybunter.click/user_profile/' + encodeURIComponent(tweet.profile_picture || '');
            img.alt = 'Profile Picture';
            card.appendChild(img);

            var content = document.createElement('div');
            content.className = 'tweet-content';

            var header = document.createElement('div');
            header.className = 'tweet-header';

            var h3 = document.createElement('h3');
            var link = document.createElement('a');
            link.href = 'profile.php?user_id=' + encodeURIComponent(tweet.user_id || '');
            link.textContent = tweet.username || 'Unknown';
            h3.appendChild(link);

            var dateEl = document.createElement('div');
            dateEl.className = 'tweet-date';
            dateEl.textContent = tweet.created_human || tweet.created_at || '';

            header.appendChild(h3);
            header.appendChild(dateEl);

            var body = document.createElement('p');
            body.textContent = tweet.content || '';

            content.appendChild(header);
            content.appendChild(body);
            card.appendChild(content);
            container.appendChild(card);
        });
    }

    // POST (first)
    if (tweetForm && tweetInput && tweetBtn) {
        tweetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var content = tweetInput.value.trim();
            if (!content) {
                alert('Please enter a tweet.');
                return;
            }

            tweetBtn.disabled = true;
            tweetBtn.innerHTML = 'Submitting...';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'tweets.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (xhr.status === 200 && data.status === 'success') {
                            window.postMessage(data, '*');
                            tweetBtn.disabled = false;
                            tweetBtn.innerHTML = 'Post Tweet';
                            tweetInput.value = '';
                            window.location.reload(); // reload so the new tweet shows immediately
                        } else {
                            window.postMessage({ error: (data && data.message) || 'Failed to post tweet.' }, '*');
                            tweetBtn.disabled = false;
                            tweetBtn.innerHTML = 'Post Tweet';
                        }
                    } catch (err) {
                        window.postMessage({ error: 'Failed to post tweet.' }, '*');
                        tweetBtn.disabled = false;
                        tweetBtn.innerHTML = 'Post Tweet';
                    }
                }
            };
            xhr.send(JSON.stringify({ tweet_content: content }));
        });
    }

    // GET (second)
        setStatus('Loading tweets...');
        var listXhr = new XMLHttpRequest();
        listXhr.open('GET', 'tweets.php', true);
        listXhr.onreadystatechange = function() {
            if (listXhr.readyState === 4) {
                if (listXhr.status === 200) {
                    try {
                        var data = JSON.parse(listXhr.responseText);
                        renderTweets(data);
                        setStatus('');
                    } catch (e) {
                        setStatus('Failed to parse tweets.');
                    }
                } else {
                    setStatus('Failed to load tweets (status ' + listXhr.status + ').');
                }
            }
        };
        listXhr.send();
});
    </script>

</html>
