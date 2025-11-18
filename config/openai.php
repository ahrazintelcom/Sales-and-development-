<?php
return [
    'api_key' => getenv('OPENAI_API_KEY') ?: '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',
    'model' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
    'base_url' => getenv('OPENAI_BASE_URL') ?: 'https://openrouter.ai/api/v1',
];
