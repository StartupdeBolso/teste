# N8N Dispatch SaaS - Frontend

Interface web em Vue.js 3 para o micro SaaS de disparos via webhook N8N.

## Tecnologias

- Vue 3 (Composition API)
- Vue Router
- Pinia (State Management)
- Axios (HTTP Client)
- Vite (Build Tool)
- TailwindCSS (Styling)

## Instalação

### 1. Instalar dependências

```bash
cd frontend
npm install
```

### 2. Configurar variáveis de ambiente

```bash
cp .env.example .env
```

Edite o arquivo `.env`:

```env
VITE_API_URL=/api  # Para desenvolvimento local
# ou
VITE_API_URL=https://your-backend-api.com/api  # Para produção
```

### 3. Executar em desenvolvimento

```bash
npm run dev
```

O app estará disponível em `http://localhost:3000`

## Build para produção

```bash
npm run build
```

Os arquivos otimizados estarão em `dist/`

## Deploy na Vercel

### Via CLI

```bash
npm install -g vercel
vercel
```

### Via GitHub

1. Conecte seu repositório ao Vercel
2. Configure as variáveis de ambiente:
   - `VITE_API_URL`: URL da API backend
3. Deploy automático em cada push

### Configuração da Vercel

Crie um arquivo `vercel.json` na raiz do frontend (opcional):

```json
{
  "buildCommand": "npm run build",
  "outputDirectory": "dist",
  "devCommand": "npm run dev",
  "installCommand": "npm install"
}
```

## Estrutura do Projeto

```
frontend/
├── src/
│   ├── assets/          # CSS e assets estáticos
│   ├── components/      # Componentes reutilizáveis
│   │   └── AppLayout.vue
│   ├── views/           # Views/páginas
│   │   ├── LoginView.vue
│   │   ├── RegisterView.vue
│   │   ├── DashboardView.vue
│   │   ├── CampaignsView.vue
│   │   ├── CampaignCreateView.vue
│   │   └── CampaignDetailView.vue
│   ├── router/          # Configuração de rotas
│   ├── stores/          # Pinia stores
│   │   ├── auth.js
│   │   └── campaigns.js
│   ├── services/        # Serviços de API
│   │   ├── api.js
│   │   ├── auth.js
│   │   ├── campaigns.js
│   │   ├── dispatches.js
│   │   └── googleSheets.js
│   ├── App.vue
│   └── main.js
├── public/
├── index.html
├── vite.config.js
├── tailwind.config.js
└── package.json
```

## Funcionalidades

### Autenticação
- Login com email e senha
- Registro de novos usuários
- JWT token armazenado no localStorage
- Redirecionamento automático para login se não autenticado

### Dashboard
- Visão geral das campanhas
- Estatísticas (total, ativas, concluídas, rascunhos)
- Lista de campanhas recentes

### Campanhas
- Listar todas as campanhas
- Criar nova campanha
- Editar campanha (apenas rascunhos)
- Deletar campanha (apenas rascunhos e pausadas)
- Iniciar campanha
- Pausar/Retomar campanha
- Visualizar detalhes e estatísticas

### Google Sheets
- Conectar planilha pelo ID
- Selecionar aba da planilha
- Preview dos dados
- Contador de contatos

### Monitoramento
- Atualização em tempo real (auto-refresh a cada 10s)
- Progresso visual dos disparos
- Estatísticas detalhadas (pendentes, processando, enviados, falhas)

## Rotas

- `/login` - Login
- `/register` - Registro
- `/` - Dashboard (requer autenticação)
- `/campaigns` - Lista de campanhas (requer autenticação)
- `/campaigns/create` - Criar campanha (requer autenticação)
- `/campaigns/:id` - Detalhes da campanha (requer autenticação)

## API Integration

O frontend se comunica com o backend via axios. Todas as requisições incluem o JWT token no header `Authorization: Bearer <token>`.

### Interceptors

- **Request**: Adiciona token JWT automaticamente
- **Response**: Redireciona para login em caso de 401 (não autorizado)

## Estilização

Utiliza TailwindCSS com classes utilitárias customizadas:

- `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-success`
- `.input`
- `.card`
- `.badge`, `.badge-draft`, `.badge-active`, etc.

## Desenvolvimento

### Adicionar nova rota

1. Criar a view em `src/views/`
2. Adicionar rota em `src/router/index.js`
3. Adicionar link de navegação

### Adicionar novo serviço de API

1. Criar arquivo em `src/services/`
2. Usar instância axios de `src/services/api.js`
3. Exportar funções que fazem requisições

### Adicionar novo store

1. Criar arquivo em `src/stores/`
2. Usar `defineStore` do Pinia
3. Importar e usar em componentes com `useNomeStore()`
