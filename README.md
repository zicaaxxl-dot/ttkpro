# TTKPro — deploy no Render (modo simples)

Site PHP + **MariaDB dentro do mesmo container**. Não precisa criar MySQL à parte.

> Aviso: no plano free do Render o disco é temporário. Se o serviço reiniciar, o banco pode voltar vazio e o SQL é reimportado automaticamente.

## O que tem neste projeto

- App principal (`ttkpro-app`)
- `database-v1.6.sql` (import automático na 1ª subida)
- `Dockerfile` (PHP 8.2 + Apache + MariaDB)

## Passo a passo no Render (GitHub)

### 1) Conta e GitHub

1. Crie conta em [https://render.com](https://render.com)
2. Crie um repositório no GitHub (ex.: `ttkpro`)
3. Envie **esta pasta** `C:\Users\Crime é bom\ttkpro` para o repositório

Se não tiver Git instalado no PC:

- Instale: [https://git-scm.com/download/win](https://git-scm.com/download/win)
- Ou use o site do GitHub → **Upload files** e arraste os arquivos da pasta (exceto pastas enormes se der problema; `vendor` precisa ir junto).

### 2) Criar o serviço

1. No Render: **New** → **Web Service**
2. Conecte o repositório `ttkpro`
3. Configure:
   - **Runtime:** Docker
   - **Branch:** main (ou master)
   - **Plan:** Free
4. Clique em **Create Web Service**
5. Espere o build (pode levar vários minutos na 1ª vez)

### 3) Abrir o site

Quando ficar **Live**, abra a URL tipo:

`https://ttkpro-xxxx.onrender.com`

Páginas úteis:

- `/` ou `/index.php` — entrada
- `/login.php` — login do painel
- `/admin.php` — admin de produtos/gateway

### 4) Senhas

O dump SQL veio com usuários do desenvolvedor. No painel do app, **troque as senhas** assim que conseguir entrar.

Se não souber a senha do `admin` do SQL, peça ao dev ou me avise que eu gero um hash novo e atualizo o dump.

Variáveis de ambiente (já no `render.yaml`):

| Variável | Função |
|----------|--------|
| `DB_HOST` | `127.0.0.1` (MariaDB local no container) |
| `DB_USER` / `DB_PASS` / `DB_NAME` | Credenciais do app |
| `MYSQL_ROOT_PASSWORD` | Root interno do MariaDB |

## Testar no PC (opcional, precisa Docker Desktop)

```bash
docker build -t ttkpro .
docker run --rm -p 8080:80 -e PORT=80 ttkpro
```

Abra: [http://localhost:8080](http://localhost:8080)

## Extensão Chrome

A pasta `[PREMIUM]` / extensão **não** sobe no Render. Instale localmente no Chrome (modo desenvolvedor) se for usar.

## Problemas comuns

- **Build demorado / falhou:** tente de novo; no free às vezes a máquina dorme.
- **Site “acordando” lento:** no free o serviço hiberna após inatividade.
- **Banco sumiu após restart:** normal no free; o SQL reimporta sozinho se as tabelas não existirem.
- **Erro 500 de banco:** veja os logs do serviço no Render (Logs).

## Próximo nível (quando for sério)

- MySQL externo (Aiven / Railway) com dados persistentes
- Subir também o `admin-app` (superadmin)
- Trocar SMTP / salt / segredos do `config.php`
