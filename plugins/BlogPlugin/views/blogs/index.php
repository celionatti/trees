<?php $this->layout('layouts.main'); ?>

<?php $this->section('title'); ?>
Blog Posts
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<h1>Blog Posts</h1>

<?php if (empty($posts)): ?>
    <p>No blog posts found.</p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <div class="post">
            <h2>
                <a href="/blog/<?= $this->e($post['id']) ?>">
                    <?= $this->e($post['title']) ?>
                </a>
            </h2>
            <p><?= $this->e($post['excerpt']) ?></p>
            <a href="/blog/<?= $this->e($post['id']) ?>">Read more...</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php $this->endSection(); ?>