-- SQL para criar as tabelas no Supabase
-- Execute este script no SQL Editor do Supabase

-- Tabela de organizações (multi-tenant)
CREATE TABLE organizations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    webhook_url VARCHAR(500),
    phone_number VARCHAR(50),
    settings JSON,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- Tabela de usuários
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER REFERENCES organizations(id) ON DELETE SET NULL,
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
    organization_id INTEGER REFERENCES organizations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    file_name VARCHAR(255),
    total_contacts INTEGER NOT NULL DEFAULT 0,
    dispatch_limit INTEGER NOT NULL DEFAULT 0,
    dispatched_count INTEGER NOT NULL DEFAULT 0,
    daily_limit INTEGER NOT NULL DEFAULT 0,
    dispatched_today INTEGER NOT NULL DEFAULT 0,
    last_dispatch_date TIMESTAMP WITH TIME ZONE,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    configuration JSON,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    started_at TIMESTAMP WITH TIME ZONE,
    completed_at TIMESTAMP WITH TIME ZONE
);

-- Tabela de contatos importados
CREATE TABLE contacts (
    id SERIAL PRIMARY KEY,
    campaign_id INTEGER NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    data JSON NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
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
CREATE INDEX idx_organizations_is_active ON organizations(is_active);
CREATE INDEX idx_users_organization_id ON users(organization_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_campaigns_user_id ON campaigns(user_id);
CREATE INDEX idx_campaigns_organization_id ON campaigns(organization_id);
CREATE INDEX idx_campaigns_status ON campaigns(status);
CREATE INDEX idx_contacts_campaign_id ON contacts(campaign_id);
CREATE INDEX idx_dispatches_campaign_id ON dispatches(campaign_id);
CREATE INDEX idx_dispatches_status ON dispatches(status);

-- Comentários
COMMENT ON TABLE organizations IS 'Tabela de organizações (multi-tenant SaaS)';
COMMENT ON TABLE users IS 'Tabela de usuários do sistema';
COMMENT ON TABLE campaigns IS 'Tabela de campanhas de disparo';
COMMENT ON TABLE contacts IS 'Tabela de contatos importados de arquivos';
COMMENT ON TABLE dispatches IS 'Tabela de disparos individuais';

-- Criar primeiro super admin (OPCIONAL - execute apenas uma vez)
-- Senha padrão: admin123 (TROQUE IMEDIATAMENTE!)
-- INSERT INTO users (email, name, password, roles) VALUES
-- ('admin@example.com', 'Super Admin', '$2y$13$password_hash_here', '["ROLE_USER", "ROLE_SUPER_ADMIN"]');
