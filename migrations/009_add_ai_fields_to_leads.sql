ALTER TABLE leads
    ADD COLUMN ai_app_type VARCHAR(255) NULL AFTER status,
    ADD COLUMN ai_insight_summary TEXT NULL AFTER ai_app_type,
    ADD COLUMN ai_app_concept TEXT NULL AFTER ai_insight_summary,
    ADD COLUMN ai_app_features LONGTEXT NULL AFTER ai_app_concept,
    ADD COLUMN ai_app_benefits LONGTEXT NULL AFTER ai_app_features,
    ADD COLUMN ai_price_min DECIMAL(12,2) NULL AFTER ai_app_benefits,
    ADD COLUMN ai_price_max DECIMAL(12,2) NULL AFTER ai_price_min,
    ADD COLUMN ai_call_script LONGTEXT NULL AFTER ai_price_max,
    ADD COLUMN ai_email_script LONGTEXT NULL AFTER ai_call_script,
    ADD COLUMN ai_talking_points LONGTEXT NULL AFTER ai_email_script,
    ADD COLUMN ai_full_proposal LONGTEXT NULL AFTER ai_talking_points,
    ADD COLUMN ai_last_generated_at DATETIME NULL AFTER ai_full_proposal;
