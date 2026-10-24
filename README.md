# Helpdesk API

API RESTful desenvolvida em PHP puro para gerenciamento de chamados de suporte, com autenticação via JWT, autorização por perfil e persistência em banco de dados MySQL.

O projeto foi desenvolvido com foco na aplicação prática de conceitos de desenvolvimento back-end, arquitetura em camadas, segurança, autenticação, autorização e boas práticas na construção de APIs.

## API em produção

A API está publicada em:

```text
https://helpdesk-api-2ypt.onrender.com
```

### Infraestrutura

- **Render** — hospedagem da API PHP
- **Aiven** — banco de dados MySQL
- **GitHub** — versionamento e integração com o deploy
- **Docker** — ambiente de execução da aplicação

---

# Funcionalidades

## Usuários

- Cadastro de usuários
- Validação de nome, e-mail e senha
- E-mail único
- Armazenamento seguro de senhas com `password_hash()`
- Login utilizando `password_verify()`
- Autenticação através de JWT
- Consulta dos dados do usuário autenticado
- Cadastro público sempre cria usuários com perfil `user`

Não existe endpoint público para promover usuários ao perfil `admin`.

---

## Chamados

- Criação de chamados
- Listagem de chamados
- Consulta por ID
- Atualização parcial utilizando `PATCH`
- Exclusão de chamados
- Atualização de status
- Associação automática do chamado ao usuário autenticado

O proprietário do chamado é definido através do JWT.

Mesmo que um `usuario_id` seja enviado no JSON, esse valor é ignorado pela aplicação.

Todo novo chamado é criado inicialmente com:

```text
status = aberto
```

---

# Autenticação

A autenticação utiliza **JSON Web Token (JWT)**.

Após o login com credenciais válidas, a API retorna um token que deve ser enviado nas rotas protegidas:

```http
Authorization: Bearer TOKEN
```

O JWT contém:

- `sub` — ID do usuário
- `perfil` — perfil do usuário
- `iat` — momento em que o token foi emitido
- `exp` — momento de expiração

Os tokens gerados possuem validade de:

```text
3600 segundos
```

ou:

```text
1 hora
```

A aplicação utiliza o algoritmo:

```text
HS256
```

A chave utilizada para assinar os tokens é fornecida através da variável de ambiente:

```text
JWT_SECRET
```

A chave deve permanecer estável entre reinicializações da aplicação.

Alterar a `JWT_SECRET` invalida os tokens emitidos anteriormente.

---

# Autorização

Existem dois perfis:

```text
user
admin
```

## Usuário comum (`user`)

Pode:

- criar chamados;
- listar seus próprios chamados;
- consultar seus próprios chamados;
- atualizar título e descrição dos próprios chamados;
- excluir seus próprios chamados.

Não pode:

- consultar chamados pertencentes a outros usuários;
- editar chamados de outros usuários;
- excluir chamados de outros usuários;
- alterar o status dos chamados;
- promover usuários para administrador.

Tentativas de consultar, editar ou excluir chamados de outros usuários retornam:

```text
404 Not Found
```

---

## Administrador (`admin`)

Pode:

- criar chamados;
- listar todos os chamados;
- consultar chamados de qualquer usuário;
- alterar o status dos chamados.

A edição de título/descrição e a exclusão continuam restritas ao **proprietário do chamado**, inclusive quando o usuário autenticado possui perfil `admin`.

---

# Rotas públicas

```text
POST /usuarios
POST /login
```

# Rotas protegidas

As rotas abaixo exigem JWT válido:

```text
GET    /me
POST   /chamados
GET    /chamados
GET    /chamados/{id}
PATCH  /chamados/{id}
DELETE /chamados/{id}
PATCH  /chamados/{id}/status
```

---

# Segurança

O projeto implementa medidas como:

- hash de senha com `password_hash()`;
- verificação através de `password_verify()`;
- autenticação JWT;
- tokens com expiração;
- validação de `sub`;
- validação de `perfil`;
- validação de `iat`;
- validação de `exp`;
- validação da estrutura do JWT;
- validação do cabeçalho JWT;
- utilização exclusiva de HS256;
- autorização por perfil;
- controle de propriedade dos chamados;
- Prepared Statements com PDO;
- proteção contra acesso a chamados de outros usuários;
- limite de tamanho dos campos;
- mensagens genéricas para erros internos;
- variáveis de ambiente para credenciais e secrets;
- secrets fora do repositório Git.

A aplicação não retorna stack traces, mensagens SQL, caminhos internos ou secrets nas respostas de erro esperadas.

---

# Arquitetura

O projeto utiliza separação de responsabilidades em camadas:

```text
Controller
    ↓
Service
    ↓
Repository
    ↓
Banco de dados
```

## Controller

Responsável pela camada HTTP:

- leitura das requisições;
- validação inicial do formato dos dados;
- definição dos códigos HTTP;
- envio das respostas JSON.

## Service

Responsável pelas regras de negócio:

- validações;
- autenticação;
- autorização;
- regras de usuários;
- regras de chamados.

## Repository

Responsável pela comunicação com o banco através de:

- PDO;
- Prepared Statements;
- consultas SQL parametrizadas.

## Middleware

Responsável pela autenticação das rotas protegidas através do JWT.

---

# Estrutura do projeto

```text
helpdesk-api/
│
├── config/
│   └── database.php
│
├── public/
│   └── index.php
│
├── src/
│   ├── Controller/
│   │   ├── ChamadoController.php
│   │   └── UsuarioController.php
│   │
│   ├── Exception/
│   │   ├── AutenticacaoException.php
│   │   ├── AutorizacaoException.php
│   │   ├── RecursoNaoEncontradoException.php
│   │   └── ValidacaoException.php
│   │
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   │
│   ├── Repository/
│   │   ├── ChamadoRepository.php
│   │   └── UsuarioRepository.php
│   │
│   └── Service/
│       ├── ChamadoService.php
│       ├── TokenService.php
│       └── UsuarioService.php
│
├── .gitignore
├── composer.json
├── composer.lock
├── Dockerfile
├── README.md
└── schema.sql
```

O diretório `vendor/` é gerado pelo Composer e não é versionado.

---

# Tecnologias utilizadas

- PHP 8
- MySQL / MariaDB
- PDO
- Composer
- Firebase PHP-JWT
- JSON
- REST
- Docker
- Git
- GitHub
- Render
- Aiven

---

# Requisitos para execução local

- PHP 8.2 ou superior
- Composer
- MySQL ou MariaDB
- extensão `PDO`
- extensão `pdo_mysql`
- extensão `mbstring`

O ambiente de produção utiliza PHP através de Docker.

---

# Instalação local

Após obter o código do projeto:

```bash
cd helpdesk-api
```

Instale as dependências:

```bash
composer install
```

---

# Banco de dados

O projeto contém:

```text
schema.sql
```

O script cria o banco:

```text
helpdesk
```

e as tabelas:

```text
usuarios
chamados
```

Existe uma chave estrangeira relacionando cada chamado ao usuário responsável por sua criação.

> O `schema.sql` cria e seleciona especificamente o banco `helpdesk`.

Caso seja utilizado outro nome através de:

```text
MYSQLDATABASE
```

também será necessário adaptar o script SQL.

---

# Variáveis de ambiente

A aplicação utiliza `getenv()`.

O projeto **não carrega arquivos `.env` automaticamente**.

Adicionar `.env` ao `.gitignore` apenas impede o versionamento desse arquivo.

## Banco

```text
MYSQLHOST
MYSQLPORT
MYSQLDATABASE
MYSQLUSER
MYSQLPASSWORD
```

Valores padrão utilizados localmente:

```text
MYSQLHOST=localhost
MYSQLPORT=3306
MYSQLDATABASE=helpdesk
MYSQLUSER=root
MYSQLPASSWORD=
```

## JWT

```text
JWT_SECRET
```

No PowerShell, uma chave pode ser gerada com:

```powershell
$env:JWT_SECRET = php -r "echo bin2hex(random_bytes(32));"
```

Isso gera 32 bytes aleatórios representados por 64 caracteres hexadecimais.

Depois de definir a variável, inicie o servidor na mesma sessão do PowerShell.

Nunca coloque a `JWT_SECRET` ou credenciais do banco:

- no código;
- no README;
- no GitHub;
- em commits públicos.

---

# Executando localmente

Na raiz do projeto:

```bash
php -S localhost:8000 -t public public/index.php
```

A API ficará disponível em:

```text
http://localhost:8000
```

---

# Validações implementadas

| Campo | Regra |
|---|---|
| Nome | Não vazio após `trim()` e até 150 caracteres |
| E-mail | Obrigatório, validado e único |
| Senha no cadastro | Entre 8 e 72 bytes |
| Senha no login | Valores acima de 72 bytes são rejeitados |
| Título | Não vazio após `trim()` e até 150 caracteres |
| Descrição | Não vazia e até 65.535 bytes |
| Status | `aberto`, `em_andamento` ou `resolvido` |

---

# Endpoints

## Cadastro de usuário

```http
POST /usuarios
```

Exemplo:

```json
{
  "nome": "Usuario Teste",
  "email": "usuario@email.com",
  "senha": "12345678"
}
```

Resposta de sucesso:

```text
201 Created
```

O cadastro público cria o usuário com:

```text
perfil = user
```

---

## Login

```http
POST /login
```

Exemplo:

```json
{
  "email": "usuario@email.com",
  "senha": "12345678"
}
```

Resposta:

```text
200 OK
```

Exemplo:

```json
{
  "mensagem": "Login realizado com sucesso.",
  "usuario": {
    "id": 1,
    "nome": "Usuario Teste",
    "email": "usuario@email.com",
    "perfil": "user",
    "created_at": "2026-10-24 12:00:00"
  },
  "token": "JWT..."
}
```

Credenciais inválidas retornam:

```text
401 Unauthorized
```

A API não informa se o erro ocorreu especificamente no e-mail ou na senha.

---

## Usuário autenticado

```http
GET /me
```

Header:

```http
Authorization: Bearer TOKEN
```

Resposta:

```text
200 OK
```

Exemplo:

```json
{
  "usuario": {
    "id": 1,
    "nome": "Usuario Teste",
    "email": "usuario@email.com",
    "perfil": "user",
    "created_at": "2026-10-24 12:00:00"
  }
}
```

---

## Criar chamado

```http
POST /chamados
```

Requer autenticação.

```json
{
  "titulo": "Problema com impressora",
  "descricao": "A impressora não está imprimindo."
}
```

Resposta:

```text
201 Created
```

O proprietário é definido pelo JWT.

Um `usuario_id` enviado pelo cliente é ignorado.

O status inicial é:

```text
aberto
```

---

## Listar chamados

```http
GET /chamados
```

Resposta:

```text
200 OK
```

Formato:

```json
{
  "chamados": []
}
```

Usuários comuns recebem apenas os próprios chamados.

Administradores podem listar todos.

---

## Buscar chamado por ID

```http
GET /chamados/{id}
```

Resposta:

```text
200 OK
```

Exemplo parcial:

```json
{
  "chamado": {
    "id": 1,
    "usuario_id": 1,
    "titulo": "Problema com impressora",
    "descricao": "A impressora não está imprimindo.",
    "status": "aberto"
  }
}
```

A resposta real também pode conter:

```text
created_at
updated_at
```

Usuários comuns só podem consultar seus próprios chamados.

Administradores podem consultar chamados de qualquer usuário.

Chamado inexistente ou inacessível:

```text
404 Not Found
```

---

## Atualizar chamado

```http
PATCH /chamados/{id}
```

A edição é permitida apenas ao proprietário.

É possível atualizar somente o título:

```json
{
  "titulo": "Novo título"
}
```

Somente a descrição:

```json
{
  "descricao": "Nova descrição"
}
```

Ou ambos:

```json
{
  "titulo": "Novo título",
  "descricao": "Nova descrição"
}
```

Para um chamado existente e acessível ao usuário, um objeto vazio:

```json
{}
```

retorna:

```text
400 Bad Request
```

Caso o chamado não exista ou não pertença ao usuário:

```text
404 Not Found
```

Resposta de sucesso:

```text
200 OK
```

---

## Excluir chamado

```http
DELETE /chamados/{id}
```

Somente o proprietário pode excluir.

Resposta de sucesso:

```text
204 No Content
```

A resposta `204` não possui corpo.

---

## Atualizar status

```http
PATCH /chamados/{id}/status
```

Disponível somente para administradores.

Exemplo:

```json
{
  "status": "em_andamento"
}
```

Status permitidos:

```text
aberto
em_andamento
resolvido
```

Resposta de sucesso:

```text
200 OK
```

Usuário comum recebe:

```text
403 Forbidden
```

---

# Contrato HTTP

| Método e rota | Sucesso |
|---|---:|
| `POST /usuarios` | `201` |
| `POST /login` | `200` |
| `GET /me` | `200` |
| `POST /chamados` | `201` |
| `GET /chamados` | `200` |
| `GET /chamados/{id}` | `200` |
| `PATCH /chamados/{id}` | `200` |
| `DELETE /chamados/{id}` | `204` |
| `PATCH /chamados/{id}/status` | `200` |

---

# Códigos HTTP

| Código | Significado |
|---|---|
| `200` | Requisição realizada com sucesso |
| `201` | Recurso criado |
| `204` | Operação realizada sem corpo de resposta |
| `400` | Dados inválidos |
| `401` | Credenciais ou autenticação inválidas |
| `403` | Usuário autenticado sem permissão |
| `404` | Recurso ou rota não encontrada |
| `405` | Método HTTP não permitido |
| `500` | Erro interno do servidor |

Nas respostas `405`, a API também envia o header:

```http
Allow
```

Exemplos:

```text
POST
GET
GET, POST
GET, PATCH, DELETE
PATCH
```

---

# Exceções personalizadas

```text
ValidacaoException
→ 400 Bad Request
```

```text
AutenticacaoException
→ 401 Unauthorized
```

```text
AutorizacaoException
→ 403 Forbidden
```

```text
RecursoNaoEncontradoException
→ 404 Not Found
```

Erros inesperados são encaminhados para o tratamento global:

```text
500 Internal Server Error
```

A resposta enviada ao cliente é genérica.

---

# Validação do JWT

Além das verificações da biblioteca, a aplicação valida:

- estrutura do token;
- estrutura do header;
- algoritmo HS256;
- tipo de `kid`, quando presente;
- existência e validade de `exp`;
- existência e validade de `iat`;
- `iat` não pode estar no futuro;
- `iat` deve ser anterior a `exp`;
- `sub` deve representar um ID inteiro positivo;
- `perfil` deve ser `user` ou `admin`.

Tokens malformados são tratados como falha de autenticação.

Problemas internos de configuração não são mascarados como erro do usuário.

---

# Docker

O projeto possui um `Dockerfile` utilizado no deploy.

O container:

- utiliza PHP;
- instala as extensões necessárias;
- instala dependências com Composer;
- copia a aplicação;
- publica o diretório `public/`;
- utiliza a porta fornecida pelo ambiente.

O servidor escuta em:

```text
0.0.0.0
```

utilizando a variável:

```text
PORT
```

fornecida pelo ambiente de hospedagem.

---

# Deploy em produção

O deploy foi realizado com:

```text
GitHub
   ↓
Render
   ↓
API PHP / Docker
   ↓
Aiven MySQL
```

## Render

Responsável por executar a API PHP através do Docker.

As seguintes variáveis são configuradas diretamente no ambiente do Render:

```text
MYSQLHOST
MYSQLPORT
MYSQLDATABASE
MYSQLUSER
MYSQLPASSWORD
JWT_SECRET
```

Nenhuma credencial de produção está armazenada no repositório.

## Aiven

Responsável pelo banco MySQL utilizado pela aplicação publicada.

O banco utilizado é:

```text
helpdesk
```

com as tabelas:

```text
usuarios
chamados
```

---

# Testes e revisão

O projeto passou por testes envolvendo:

- cadastro;
- login;
- autenticação;
- JWT válido;
- JWT expirado;
- JWT malformado;
- assinatura adulterada;
- autorização;
- CRUD completo;
- isolamento entre usuários;
- códigos HTTP;
- métodos inválidos;
- header `Allow`;
- rotas inexistentes;
- limites dos campos;
- persistência;
- tratamento de exceções;
- proteção contra exposição de erros internos.

## Validação local

Na revisão final local foram executadas:

```text
83 requisições HTTP
104 verificações aprovadas
0 falhas
```

Também foi realizada verificação de sintaxe dos arquivos PHP da aplicação sem erros.

Esses resultados correspondem à revisão local realizada durante o desenvolvimento.

Os scripts utilizados nessa revisão não estão versionados atualmente como uma suíte automatizada de testes.

---

## Validação em produção

Após o deploy, a API pública também foi testada.

Foram realizadas:

```text
42 requisições HTTP
68 verificações aprovadas
0 falhas
```

Entre os cenários validados em produção:

- cadastro válido;
- cadastro duplicado;
- senha curta;
- login válido;
- senha incorreta;
- senha acima de 72 bytes;
- `/me` com token;
- `/me` sem token;
- JWT malformado;
- assinatura JWT adulterada;
- Bearer sem diferenciação de maiúsculas/minúsculas;
- criação de chamado;
- validação de chamado;
- listagem;
- consulta por ID;
- PATCH;
- PATCH vazio;
- DELETE;
- consulta após exclusão;
- isolamento entre usuários;
- bloqueio de edição de chamado alheio;
- bloqueio de exclusão de chamado alheio;
- bloqueio de alteração de status por usuário comum;
- rota inexistente;
- métodos HTTP inválidos;
- header `Allow`;
- respostas JSON;
- persistência entre requisições e novos logins;
- ausência de exposição de stack traces, SQL, caminhos internos ou secrets.

O `DELETE` foi confirmado retornando:

```text
204 No Content
```

sem corpo.

---

# Limitações atuais

Alguns pontos estão fora do escopo atual do projeto:

- não existe endpoint para promover usuários a administrador;
- não existe endpoint para excluir usuários;
- não existe mecanismo de revogação imediata de JWT;
- alterações de perfil no banco não invalidam automaticamente tokens já emitidos;
- não existe suíte automatizada de testes versionada;
- persistência após reinicialização completa da infraestrutura não foi testada diretamente;
- cold start do serviço gratuito não foi provocado de forma controlada durante a validação.

As permissões administrativas estão implementadas no código.

A validação pública confirmou que usuários comuns não conseguem executar operações administrativas.

---

# Production API

```text
https://helpdesk-api-2ypt.onrender.com
```

---

# Objetivo do projeto

Este projeto foi desenvolvido como parte dos meus estudos em desenvolvimento back-end.

O objetivo foi ir além de um CRUD simples e implementar conceitos utilizados em aplicações reais, incluindo:

- autenticação;
- autorização;
- segurança de senhas;
- JWT;
- controle de acesso;
- arquitetura em camadas;
- regras de negócio;
- códigos HTTP;
- validação de entrada;
- banco de dados relacional;
- Prepared Statements;
- tratamento de exceções;
- proteção de recursos por proprietário;
- variáveis de ambiente;
- Docker;
- deploy em nuvem;
- banco de dados em produção.

---

# Autor

**Neidson Fagundes**

Desenvolvedor em formação com foco em desenvolvimento back-end, APIs e construção de soluções utilizando PHP.