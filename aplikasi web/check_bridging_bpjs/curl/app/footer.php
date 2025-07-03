<?php
// Key rahasia untuk enkripsi dan dekripsi footer
$footer_encryption_key = "my_super_secret_key";

// Isi footer
$footer_content = "&copy; " . date("Y") . " M. Wira Sb. S. Kom. All rights reserved.";

// Enkripsi footer content
$encrypted_footer = aes_encrypt($footer_content, $footer_encryption_key);

// Tampilkan footer
?>
<footer>
    <?php if ($footer_hash !== md5($footer_content)) : ?>
        <p class="error">wira@rsph2024</p>
    <?php else : ?>
        <?php echo aes_decrypt($encrypted_footer, $footer_encryption_key); ?>
    <?php endif; ?>
</footer>
