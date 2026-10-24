# Helpdesk API

API RESTful desenvolvida em PHP puro para gerenciamento de chamados de suporte, com autenticação via JWT, controle de acesso por perfil e persistência em banco de dados MySQL/MariaDB.

O projeto foi desenvolvido com foco no aprendizado e aplicação prática de conceitos de desenvolvimento back-end, organização em camadas, segurança, autenticação, autorização e boas práticas no desenvolvimento de APIs.

## Funcionalidades

### Usuários

- Cadastro de usuários
- Validação de nome, e-mail e senha
- E-mail único
- Armazenamento seguro de senhas com `password_hash()`
- Login com `password_verify()`
- Autenticação através de JWT
- Consulta dos dados do usuário autenticado

### Chamados

- Criação de chamados
- Listagem de chamados
- Consulta por ID
- Atualização parcial com `PATCH`
- Exclusão de chamados
- Atualização de status
- Associação automática do chamado ao usuário autenticado

### Autorização

Existem dois perfis:

- `user`
- `admin`

Usuários comuns podem:

- criar chamados;
- visualizar seus próprios chamados;
- consultar seus próprios chamados;
- atualizar seus próprios chamados;
- excluir seus próprios chamados.

Administradores podem:

- listar todos os chamados;
- consultar chamados de qualquer usuário;
- alterar o status dos chamados.

Somente administradores podem alterar o status de um chamado.

## Segurança

O projeto implementa diversas medidas de segurança:

- Senhas armazenadas utilizando hash seguro
- Autenticação com JWT
- Tokens com tempo de expiração
- Validação de `iat`, `exp`, `sub` e `perfil`
- Assinatura JWT utilizando `HS256`
- Validação da estrutura do JWT antes da decodificação
- Controle de autorização por perfil
- Controle de propriedade dos chamados
- Prepared Statements com PDO
- Proteção contra acesso a chamados pertencentes a outros usuários
- Respostas genéricas para erros internos
- Secrets e dados de conexão através de variáveis de ambiente
- `.env` e diretório `vendor` ignorados pelo Git

## Arquitetura

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

### Controller

Responsável pela camada HTTP:

- leitura das requisições;
- validação básica do formato dos dados;
- definição dos códigos HTTP;
- envio das respostas JSON.

### Service

Responsável pelas regras de negócio:

- validações;
- autenticação;
- autorização;
- regras relacionadas aos usuários e chamados.

### Repository

Responsável pela comunicação com o banco de dados utilizando PDO e Prepared Statements.

## Estrutura do projeto

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

## Tecnologias utilizadas

- PHP 8
- MySQL / MariaDB
- PDO
- Composer
- Firebase PHP-JWT
- JSON
- REST
- Git
- GitHub

## Requisitos

Para executar o projeto localmente:

- PHP 8.2 ou superior
- Composer
- MySQL ou MariaDB
- extensão `pdo_mysql`
- extensão `mbstring`

## Instalação

Clone o repositório:

```bash
git clone URL_DO_REPOSITORIO
```

Entre na pasta:

```bash
cd helpdesk-api
```

Instale as dependências:

```bash
composer install
```

## Banco de dados

Execute o arquivo:

```text
schema.sql
```

O script cria o banco e as tabelas necessárias para a aplicação.

As principais tabelas são:

```text
usuarios
chamados
```

Existe uma chave estrangeira ligando cada chamado ao usuário responsável por sua criação.

## Variáveis de ambiente

A aplicação utiliza variáveis de ambiente para dados sensíveis e configurações.

### Banco de dados

```text
MYSQLHOST
MYSQLPORT
MYSQLDATABASE
MYSQLUSER
MYSQLPASSWORD
```

Caso essas variáveis não estejam definidas no ambiente local, a aplicação possui valores padrão para desenvolvimento.

### JWT

É obrigatório definir:

```text
JWT_SECRET
```

No PowerShell, uma chave segura pode ser gerada com:

```powershell
$env:JWT_SECRET = php -r "echo bin2hex(random_bytes(32));"
```

A chave gerada possui 64 caracteres hexadecimais.

Não coloque a `JWT_SECRET` diretamente no código ou no GitHub.

## Executando localmente

Na raiz do projeto:

```bash
php -S localhost:8000 -t public public/index.php
```

A API estará disponível em:

```text
http://localhost:8000
```

## Endpoints

### Cadastro de usuário

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

Resposta esperada:

```text
201 Created
```

---

### Login

```http
POST /login
```

```json
{
  "email": "usuario@email.com",
  "senha": "12345678"
}
```

Resposta:

```json
{
  "mensagem": "Login realizado com sucesso.",
  "usuario": {
    "id": 1,
    "nome": "Usuario Teste",
    "email": "usuario@email.com",
    "perfil": "user"
  },
  "token": "JWT..."
}
```

---

### Dados do usuário autenticado

```http
GET /me
```

Requer:

```http
Authorization: Bearer TOKEN
```

---

### Criar chamado

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

O `usuario_id` não é recebido pelo JSON.

A API identifica automaticamente o usuário através do JWT.

---

### Listar chamados

```http
GET /chamados
```

Para usuários comuns:

```text
retorna apenas os próprios chamados
```

Para administradores:

```text
retorna todos os chamados
```

---

### Buscar chamado

```http
GET /chamados/{id}
```

Usuários comuns só podem acessar seus próprios chamados.

Administradores podem consultar qualquer chamado.

---

### Atualizar chamado

```http
PATCH /chamados/{id}
```

É possível atualizar apenas um campo:

```json
{
  "titulo": "Novo título"
}
```

ou:

```json
{
  "descricao": "Nova descrição"
}
```

ou ambos.

---

### Excluir chamado

```http
DELETE /chamados/{id}
```

Resposta em caso de sucesso:

```text
204 No Content
```

---

### Atualizar status

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

## Códigos HTTP utilizados

| Código | Significado |
|---|---|
| `200` | Requisição realizada com sucesso |
| `201` | Recurso criado com sucesso |
| `204` | Operação realizada sem conteúdo de resposta |
| `400` | Dados enviados são inválidos |
| `401` | Usuário não autenticado ou token inválido |
| `403` | Usuário autenticado sem permissão |
| `404` | Recurso ou rota não encontrada |
| `405` | Método HTTP não permitido |
| `500` | Erro interno do servidor |

Para respostas `405`, a API também informa os métodos permitidos através do header HTTP `Allow`.

## Exceções personalizadas

A aplicação utiliza exceções específicas para diferenciar corretamente os erros:

```text
ValidacaoException
→ 400 Bad Request

AutenticacaoException
→ 401 Unauthorized

AutorizacaoException
→ 403 Forbidden

RecursoNaoEncontradoException
→ 404 Not Found
```

Erros inesperados são tratados globalmente e retornam:

```text
500 Internal Server Error
```

sem exposição de detalhes internos ao cliente.

## Testes e revisão

O projeto passou por testes de:

- cadastro;
- login;
- autenticação;
- validação de JWT;
- autorização;
- CRUD de chamados;
- isolamento entre usuários;
- acesso administrativo;
- códigos HTTP;
- métodos não permitidos;
- rotas inexistentes;
- validações de entrada;
- limites dos campos;
- tratamento de erros;
- persistência no banco de dados.

Na revisão final foram executadas:

```text
83 requisições HTTP
104 verificações aprovadas
0 falhas
```

Também foi realizada verificação de sintaxe dos arquivos PHP da aplicação sem erros encontrados.

## Deploy

O projeto foi preparado para utilização de variáveis de ambiente, permitindo publicação em plataformas de hospedagem como Railway.

Após o deploy, a URL pública da API poderá ser adicionada aqui:

```text
Production API:
EM BREVE
```

## Objetivo do projeto

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
- tratamento de exceções;
- segurança de APIs.

## Autor

**Neidson Fagundes**

Desenvolvedor em formação com foco em desenvolvimento back-end e construção de APIs.