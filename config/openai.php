<?php
return [
    'api_key' => getenv('OPENAI_API_KEY') ?: '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',
    'model' => getenv('OPENAI_MODEL') ?: 'gpt-5',
    'base_url' => getenv('OPENAI_BASE_URL') ?: 'https://openrouter.ai/api/v1',
];
