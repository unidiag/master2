<?php
declare(strict_types=1);

/** @var array $flashes */

?>




<?php foreach ($flashes as $flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endforeach; ?>
 

