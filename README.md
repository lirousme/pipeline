# Sistema de Login Minimalista (POO + MVC + DDD)

## Stack
- JavaScript puro
- PHP
- MySQL
- TailwindCSS em dark mode

## Estrutura
- `src/Domain`: entidades e contratos
- `src/Application`: casos de uso (login/cadastro)
- `src/Infrastructure`: persistência MySQL e conexão
- `src/Presentation`: controller HTTP
- `public`: interface e endpoints

## Configuração
1. Copie `.env.example` para `.env` e ajuste credenciais.
2. Crie o banco e rode o script `database/schema.sql`.
3. Inicie o servidor:

```bash
php -S 127.0.0.1:8000 -t public
```

### Timezone padrão (Brasília)
- O sistema PHP usa `APP_TIMEZONE` (padrão: `America/Sao_Paulo`).
- Cada conexão MySQL aplica `SET time_zone = '-03:00'` para manter o horário de Brasília em todos os ambientes.
- A coluna `users.created_at` usa `DATETIME` e é preenchida pela aplicação PHP com `APP_TIMEZONE` (padrão `America/Sao_Paulo`), evitando depender do fuso da sessão do MySQL.
- Isso evita conversão automática de fuso do MySQL (`TIMESTAMP`) e mantém o valor gravado exatamente no horário local esperado.

Se sua tabela já existe com `TIMESTAMP`, aplique:

```sql
ALTER TABLE users MODIFY created_at DATETIME NOT NULL;
```

## Requisitos atendidos
- Formulário único para login e criação de conta.
- Alteração visual por modo:
  - Azul: login
  - Verde: criar conta
- Apenas dois campos: usuário e senha.
- Senha criptografada com `Argon2id` com parâmetros fortes.


## Deploy em subpasta (`public_html/login`)
- O projeto já inclui `index.php`, `auth.php` e `pipeline_api.php` na raiz do repositório como ponte para a pasta `public`.
- Assim, ao publicar em `public_html/login`, a URL `https://seusite.com/login/` funciona sem precisar acessar `/login/public`.
