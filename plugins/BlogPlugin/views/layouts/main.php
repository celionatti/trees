<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->yield('title', 'Blog') ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        nav {
            background: #333;
            color: white;
            padding: 10px;
            margin-bottom: 20px;
        }

        nav a {
            color: white;
            margin-right: 15px;
            text-decoration: none;
        }

        .post {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .post h2 {
            margin-top: 0;
        }
    </style>
</head>

<body>
    <nav>
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
        <a href="/blog/create">New Post</a>
    </nav>

    <main>
        <?= $this->yield('content') ?>
    </main>

    <footer style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center;">
        <p>&copy; <?= date('Y') ?> Blog Plugin</p>
    </footer>
</body>

</html>