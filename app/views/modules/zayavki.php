<?php


/** @var array $data */

?><div class="cards">
            <?php foreach ($data['rows'] as $row): ?>
                <article
                    class="card <?= is_done($row) ? 'done' : '' ?>"
                    data-status="<?= is_done($row) ? 'done' : 'open' ?>"
                >
                    <div class="card-head"><div><span class="id">#<?= e($row['id']) ?></span><h2><?= e($row['abonent'] ?: $row['abonent_ajax'] ?: 'Без имени') ?></h2></div><span class="status <?= is_done($row)?'status-done':'status-open' ?>"><?= e(format_datetime($row['time'])) ?></span></div>
                    <?php
                    $ticketAddress = trim(
                        (string) (
                            $row['address']
                            ?: $row['address_ajax']
                            ?: ''
                        )
                    );
                    ?>

                    <div class="address">
                        <?php if ($ticketAddress !== ''): ?>
                            <a
                                class="address-link"
                                href="<?= e(url([
                                    'module' => 'stat',
                                    'house' => $ticketAddress,
                                ])) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?= e($ticketAddress) ?>
                            </a>
                        <?php else: ?>
                            Адрес не указан
                        <?php endif; ?>
                    </div>


                    <?php if (!empty($row['disconnect'])): ?>
                        <?php
                        $disconnectTimestamp = strtotime(
                            (string) (
                                $row['disconnect']['created_at']
                                ?? ''
                            )
                        );
                        ?>

                        <div class="subscriber-disconnected">
                            ОТКЛЮЧЁН
                            <?php if ($disconnectTimestamp !== false): ?>
                                <?= e(date(
                                    'd.m.Y',
                                    $disconnectTimestamp
                                )) ?>
                                в
                                <?= e(date(
                                    'H:i:s',
                                    $disconnectTimestamp
                                )) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>


                    <p><?= e($row['desc']) ?></p>
                    <?php if ($row['other']): ?><div class="muted"><?= e($row['other']) ?></div><?php endif; ?>
                    <dl class="meta"><?php if (is_done($row)): ?><div><dt>Мастер</dt><dd><?= e($row['master']) ?></dd></div><div><dt>Результат</dt><dd><?= e($row['result']) ?></dd></div><div><dt>Стоимость</dt><dd><?= e($row['cost'] ?: '—') ?></dd></div><?php endif; ?></dl>
                    <div class="actions">
                        <?php if (!is_done($row)): ?>
                            <?php if (current_user() !== 'kassa'): ?>
                                <button
                                    class="button primary"
                                    type="button"
                                    data-complete='<?= e(json_encode([
                                        'id' => $row['id'],
                                        'type' => 'ticket',
                                        'disconnected' => !empty($row['disconnect']),
                                    ], JSON_UNESCAPED_UNICODE)) ?>'
                                >
                                    Выполнить
                                </button>
                            <?php endif; ?>

                            <form
                                method="post"
                                onsubmit="return confirm('Снять заявку? Она будет перенесена в выполненные.')"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >

                                <input type="hidden" name="action" value="withdraw">
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= e($row['id']) ?>"
                                >

                                <button class="button danger" type="submit">
                                    Снять
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            </div>
