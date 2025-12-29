-- SQL para criar as tabelas no Supabase
-- Execute este script no SQL Editor do Supabase

-- Tabela de usuários
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(180) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    roles JSON NOT NULL DEFAULT '["ROLE_USER"]',
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- Tabela de campanhas
CREATE TABLE campaigns (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    google_sheet_id VARCHAR(500) NOT NULL,
    sheet_name VARCHAR(255),
    total_contacts INTEGER NOT NULL DEFAULT 0,
    dispatch_limit INTEGER NOT NULL DEFAULT 0,
    dispatched_count INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    configuration JSON,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    started_at TIMESTAMP WITH TIME ZONE,
    completed_at TIMESTAMP WITH TIME ZONE
);

-- Tabela de disparos
CREATE TABLE dispatches (
    id SERIAL PRIMARY KEY,
    campaign_id INTEGER NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    contact_data JSON NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    response_data TEXT,
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    sent_at TIMESTAMP WITH TIME ZONE
);

-- Índices para melhorar performance
CREATE INDEX idx_campaigns_user_id ON campaigns(user_id);
CREATE INDEX idx_campaigns_status ON campaigns(status);
CREATE INDEX idx_dispatches_campaign_id ON dispatches(campaign_id);
CREATE INDEX idx_dispatches_status ON dispatches(status);
CREATE INDEX idx_users_email ON users(email);

-- Comentários
COMMENT ON TABLE users IS 'Tabela de usuários do sistema';
COMMENT ON TABLE campaigns IS 'Tabela de campanhas de disparo';
COMMENT ON TABLE dispatches IS 'Tabela de disparos individuais';
