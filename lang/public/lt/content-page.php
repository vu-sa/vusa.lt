<?php

return [
    // Rendered as a label followed by a separate `<time>` element (LastUpdatedFooter.vue)
    // rather than a `:date` placeholder — that keeps the static, absolute date inside a
    // real `<time datetime="…">`, which a string-substitution placeholder couldn't do
    // without an unnecessary `v-html`.
    'last_updated_footer' => 'Puslapio informacija paskutinį kartą atnaujinta',
];
