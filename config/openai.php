<?php
return [
    'api_key' => getenv('OPENAI_API_KEY') ?: '',
    'model' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
    'base_url' => getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1',
];
