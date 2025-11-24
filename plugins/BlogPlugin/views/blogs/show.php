<?php $this->layout('layouts.main'); ?>

<?php $this->section('title'); ?>
<?= $this->e($post['title']) ?> - Blog
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<article class="post">
    <h1><?= $this->e($post['title']) ?></h1>
    
    <div style="color: #666; margin-bottom: 20px;">
        <span>By <?= $this->e($post['author']) ?></span> | 
        <span><?= $this->e($post['date']) ?></span>
    </div>
    
    <div class="content">
        <?= nl2br($this->e($post['content'])) ?>
    </div>
    
    <div style="margin-top: 30px;">
        <a href="/blog">&larr; Back to all posts</a>
    </div>
</article>
<?php $this->endSection(); ?>