# Helpdesk API

API RESTful desenvolvida em PHP puro para gerenciamento de chamados de suporte, com autenticação via JWT, controle de acesso por perfil e persistência em banco de dados MySQL/MariaDB.

O projeto foi desenvolvido com foco na aplicação prática de conceitos de desenvolvimento back-end, arquitetura em camadas, segurança, autenticação, autorização e boas práticas na construção de APIs.

---

## Funcionalidades

### Usuários

- Cadastro de usuários
- Validação de nome, e-mail e senha
- E-mail único
- Senhas armazenadas com `password_hash()`
- Login utilizando `password_verify()`
- Autenticação através de JWT
- Consulta dos dados do usuário autenticado
- Cadastro público sempre cria usuários com perfil `user`

Não existe endpoint público para promover usuários ao perfil `admin`.

---

### Chamados

- Criação de chamados
- Listagem de chamados
- Consulta por ID
- Atualização parcial utilizando `PATCH`
- Exclusão de chamados
- Atualização de status
- Associação automática do chamado ao usuário autenticado

O proprietário do chamado é definido através do JWT.

Mesmo que um `usuario_id` seja enviado no JSON, esse valor é ignorado pela aplicação.

---

## Autenticação

A autenticação utiliza JSON Web Token (JWT).

Após realizar login com credenciais válidas, a API retorna um token que deve ser enviado nas rotas protegidas:

```http
Authorization: Bearer TOKEN
```

O JWT contém informações utilizadas pela aplicação, incluindo:

- `sub` — ID do usuário
- `perfil` — perfil do usuário
- `iat` — momento em que o token foi emitido
- `exp` — momento de expiração

Os tokens gerados possuem validade de **1 hora**.

A aplicação utiliza o algoritmo:

```text
HS256
```

A chave utilizada para assinar o token deve ser mantida estável entre reinicializações da aplicação.

Se a `JWT_SECRET` for alterada, os tokens emitidos anteriormente deixam de ser válidos.

---

## Autorização

Existem dois perfis:

```text
user
admin
```

### Usuário comum (`user`)

Pode:

- criar chamados;
- listar seus próprios chamados;
- consultar seus próprios chamados;
- atualizar título e descrição dos próprios chamados;
- excluir seus próprios chamados.

Não pode:

- consultar chamados pertencentes a outros usuários;
- alterar o status dos chamados;
- promover usuários para administrador.

---

### Administrador (`admin`)

Pode:

- criar chamados;
- listar todos os chamados;
- consultar chamados de qualquer usuário;
- alterar o status dos chamados.

A edição de título/descrição e a exclusão continuam sendo restritas ao **proprietário do chamado**, inclusive quando o usuário autenticado possui perfil `admin`.

---

## Rotas públicas e protegidas

### Rotas públicas

```text
POST /usuarios
POST /login
```

### Rotas protegidas por JWT

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

## Segurança

O projeto implementa medidas como:

- senhas armazenadas através de hash;
- autenticação com JWT;
- tokens com tempo de expiração;
- validação dos claims `sub`, `perfil`, `iat` e `exp`;
- assinatura JWT utilizando HS256;
- validação da estrutura do JWT antes da decodificação;
- validação do cabeçalho JWT;
- autorização por perfil;
- controle de propriedade dos chamados;
- Prepared Statements utilizando PDO;
- proteção contra acesso indevido a chamados de outros usuários;
- respostas genéricas para erros internos;
- variáveis de ambiente para credenciais e secrets;
- `.env` e `vendor/` ignorados pelo Git;
- limite de tamanho para campos sensíveis.

Usuários comuns que tentam consultar, editar ou excluir chamados pertencentes a outros usuários recebem `404`, evitando revelar a existência do recurso.

Administradores possuem as permissões adicionais descritas anteriormente.

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
- regras relacionadas a usuários;
- regras relacionadas a chamados.

## Repository

Responsável pela comunicação com o banco de dados através de:

- PDO;
- Prepared Statements;
- consultas SQL parametrizadas.

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
├── README.md
└── schema.sql
```

O diretório `vendor/` não aparece na estrutura porque é gerado através do Composer e não é versionado no Git.

---

# Tecnologias utilizadas

- PHP 8
- PHP-JWT
- MySQL / MariaDB
- PDO
- Composer
- JSON
- REST
- Git
- GitHub

---

# Requisitos

Para executar o projeto localmente:

- PHP 8.2 ou superior
- Composer
- MySQL ou MariaDB
- extensão `PDO`
- extensão `pdo_mysql`
- extensão `mbstring`

---

# Instalação

Após obter o código do projeto, entre na pasta:

```bash
cd helpdesk-api
```

Instale as dependências:

```bash
composer install
```

Após a publicação do repositório no GitHub, a URL oficial de clone poderá ser adicionada a esta seção.

---

# Banco de dados

Execute o arquivo:

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

Existe uma chave estrangeira que relaciona cada chamado ao usuário responsável pela criação.

> O `schema.sql` cria e seleciona especificamente o banco `helpdesk`.

Se você definir outro nome através de:

```text
MYSQLDATABASE
```

também será necessário ajustar o nome utilizado no `schema.sql`.

A execução do script requer permissão para criar banco de dados e tabelas.

---

# Variáveis de ambiente

A aplicação utiliza variáveis do ambiente do processo através de:

```php
getenv()
```

O projeto **não carrega arquivos `.env` automaticamente**.

Adicionar `.env` ao `.gitignore` apenas impede que esse arquivo seja enviado ao Git. Isso não significa que exista suporte automático a dotenv.

---

## Banco de dados

As seguintes variáveis podem ser utilizadas:

```text
MYSQLHOST
MYSQLPORT
MYSQLDATABASE
MYSQLUSER
MYSQLPASSWORD
```

Valores utilizados como padrão no ambiente local:

```text
MYSQLHOST=localhost
MYSQLPORT=3306
MYSQLDATABASE=helpdesk
MYSQLUSER=root
MYSQLPASSWORD=
```

---

## JWT

É obrigatório definir:

```text
JWT_SECRET
```

A chave deve possuir tamanho suficiente para uso seguro com HS256.

O ambiente utilizado no desenvolvimento exige pelo menos **32 bytes**.

No PowerShell, é possível gerar uma chave segura com:

```powershell
$env:JWT_SECRET = php -r "echo bin2hex(random_bytes(32));"
```

O comando gera 32 bytes aleatórios representados por 64 caracteres hexadecimais.

Depois de definir a variável, inicie o servidor na **mesma sessão do PowerShell**.

Não coloque a `JWT_SECRET`:

- diretamente no código;
- no README;
- no GitHub;
- em commits;
- em arquivos públicos.

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
| Nome | Não pode ficar vazio após `trim()` e possui limite de 150 caracteres |
| E-mail | Obrigatório, validado e único |
| Senha no cadastro | De 8 a 72 bytes |
| Senha no login | Valores acima de 72 bytes são rejeitados |
| Título | Não pode ficar vazio após `trim()` e possui limite de 150 caracteres |
| Descrição | Não pode ficar vazia e possui limite de 65.535 bytes |
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

O cadastro público cria usuários com:

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

Resposta de sucesso:

```text
200 OK
```

Exemplo de resposta:

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

Credenciais incorretas retornam:

```text
401 Unauthorized
```

A resposta não informa se o erro ocorreu especificamente no e-mail ou na senha.

---

# Dados do usuário autenticado

```http
GET /me
```

Requer:

```http
Authorization: Bearer TOKEN
```

Resposta de sucesso:

```text
200 OK
```

Formato:

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

# Criar chamado

```http
POST /chamados
```

Requer autenticação.

Exemplo:

```json
{
  "titulo": "Problema com impressora",
  "descricao": "A impressora não está imprimindo."
}
```

Resposta de sucesso:

```text
201 Created
```

O proprietário é definido pelo JWT.

Um `usuario_id` enviado no JSON é ignorado.

O chamado é criado inicialmente com:

```text
status = aberto
```

---

# Listar chamados

```http
GET /chamados
```

Resposta de sucesso:

```text
200 OK
```

Formato:

```json
{
  "chamados": []
}
```

Para usuários comuns:

```text
retorna somente os chamados pertencentes ao usuário autenticado
```

Para administradores:

```text
retorna todos os chamados
```

---

# Buscar chamado por ID

```http
GET /chamados/{id}
```

Resposta de sucesso:

```text
200 OK
```

Formato:

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

Usuários comuns só conseguem consultar seus próprios chamados.

Administradores podem consultar chamados de qualquer usuário.

Chamado inexistente ou chamado de outro usuário sem permissão:

```text
404 Not Found
```

---

# Atualizar chamado

```http
PATCH /chamados/{id}
```

A edição de título e descrição é permitida apenas ao proprietário do chamado.

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

O `PATCH` precisa receber pelo menos um desses campos.

Um objeto vazio:

```json
{}
```

resulta em:

```text
400 Bad Request
```

Resposta de sucesso:

```text
200 OK
```

---

# Excluir chamado

```http
DELETE /chamados/{id}
```

Somente o proprietário pode excluir o chamado.

Resposta de sucesso:

```text
204 No Content
```

A resposta `204` não possui corpo.

---

# Atualizar status

```http
PATCH /chamados/{id}/status
```

Disponível somente para usuários com perfil:

```text
admin
```

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

Usuário comum tentando executar essa operação recebe:

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

# Códigos HTTP utilizados

| Código | Significado |
|---|---|
| `200` | Requisição realizada com sucesso |
| `201` | Recurso criado com sucesso |
| `204` | Operação realizada sem conteúdo de resposta |
| `400` | Dados enviados são inválidos |
| `401` | Usuário não autenticado, credenciais inválidas ou token inválido |
| `403` | Usuário autenticado sem permissão para executar a operação |
| `404` | Recurso ou rota não encontrada |
| `405` | Método HTTP não permitido |
| `500` | Erro interno do servidor |

Quando uma rota existe, mas o método HTTP utilizado não é permitido, a API também envia o header:

```http
Allow
```

Exemplos de valores possíveis:

```text
POST
GET
GET, POST
GET, PATCH, DELETE
PATCH
```

---

# Exceções personalizadas

A aplicação utiliza exceções específicas para diferenciar os erros da API.

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

A resposta enviada ao cliente é genérica, evitando exposição de detalhes internos.

---

# JWT

Além da validação realizada pela biblioteca, a aplicação verifica informações necessárias para o contrato de autenticação.

Entre as validações implementadas estão:

- estrutura do token;
- estrutura do header;
- algoritmo HS256;
- tipo do `kid`, quando presente;
- existência e validade de `exp`;
- existência e validade de `iat`;
- `iat` não pode estar no futuro;
- `iat` precisa ser anterior a `exp`;
- `sub` precisa representar um ID inteiro positivo;
- perfil precisa ser `user` ou `admin`.

Tokens malformados são rejeitados como falha de autenticação.

Problemas internos de configuração do JWT continuam sendo tratados como erros internos do servidor.

---

# Testes e revisão

O projeto passou por revisões e testes envolvendo:

- cadastro;
- login;
- autenticação;
- tokens válidos e inválidos;
- JWT expirado;
- JWT malformado;
- autorização;
- CRUD completo;
- isolamento entre usuários;
- acesso administrativo;
- códigos HTTP;
- métodos HTTP inválidos;
- header `Allow`;
- rotas inexistentes;
- validações de entrada;
- limites de campos;
- erros de banco;
- tratamento de exceções;
- persistência em MySQL/MariaDB.

Na última revisão local foram executadas:

```text
83 requisições HTTP
104 verificações aprovadas
0 falhas
```

Também foi realizada verificação de sintaxe dos arquivos PHP da aplicação sem erros.

> Esses resultados correspondem à revisão local realizada durante o desenvolvimento.

Os scripts utilizados nessa revisão não fazem parte atualmente do repositório como uma suíte automatizada de testes.

---

# Deploy

O projeto foi preparado para utilizar variáveis de ambiente e pode ser publicado em plataformas de hospedagem compatíveis com PHP e MySQL.

O deploy ainda não está documentado como validado em produção.

Para publicação será necessário garantir:

- PHP compatível com os requisitos do projeto;
- execução do `composer install`;
- extensões PHP necessárias;
- configuração da raiz pública como `public/`;
- encaminhamento das rotas da API para `public/index.php`;
- banco MySQL/MariaDB disponível;
- inicialização do banco;
- variáveis de ambiente configuradas;
- `JWT_SECRET` segura e persistente;
- HTTPS no ambiente público.

O comando:

```bash
php -S localhost:8000 -t public public/index.php
```

é destinado ao desenvolvimento local e não deve ser tratado como configuração definitiva de produção.

Após o deploy, a URL pública será adicionada aqui:

```text
Production API:
EM BREVE
```

---

# Limitações atuais

Alguns pontos fazem parte do escopo atual do projeto:

- não existe endpoint para promover usuários a administrador;
- não existe mecanismo de revogação imediata de JWT;
- alterações de perfil no banco não invalidam automaticamente tokens já emitidos;
- não existe suíte automatizada de testes versionada no projeto;
- configuração de proxy, HTTPS e servidor de produção depende do ambiente utilizado para deploy.

---

# Objetivo do projeto

Este projeto foi desenvolvido como parte dos meus estudos em desenvolvimento back-end.

O objetivo foi ir além de um CRUD simples e implementar conceitos utilizados em aplicações reais, como:

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
- segurança básica de APIs.

---

# Autor

**Neidson Fagundes**

Desenvolvedor em formação com foco em desenvolvimento back-end, APIs e construção de soluções utilizando PHP.