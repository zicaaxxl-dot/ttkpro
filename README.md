# TTKPro — deploy no Render (plano pago + disco persistente)

Site PHP + **MariaDB no mesmo container**, com dados em disco persistente (`/var/data`).

## Persistencia (importante)

No plano pago, o `render.yaml` anexa um **disco** em `/var/data`:

- Banco MariaDB → `/var/data/mysql`
- Arquivos de pagamento → `/var/data/payments`

Assim produto/pagina **nao somem** no redeploy. Sem o disco, o container volta limpo.

### Se o servico ja existia no Render

1. Confirme o plano **Standard** (ou superior)
2. Em **Disks** do servico: monte disco em `/var/data` (min. 1–5 GB) **ou** sincronize o Blueprint (`render.yaml`)
3. Redeploy
4. **Publique de novo** os produtos (dados do plano free nao migram sozinhos)

## Paginas uteis

| URL | Funcao |
|-----|--------|
| `/login` | Login (`admin` / senha do env ou `admin123`) |
| `/admin` | Editor (salvar + publicar) |
| `/produtos` | Listar / editar / excluir produtos do servidor |
| `/vendas` | Vendas PIX |
| `/p/{slug}` | Pagina publica do produto |

## Fluxo recomendado

1. Login → **Produtos** ou **Editor**
2. Criar/editar → **Salvar no Servidor**
3. **Publicar Pagina** → link `/p/...` fica no ar
4. Para editar depois: **Produtos** → **Editar**

## Variaveis de ambiente

| Variavel | Funcao |
|----------|--------|
| `DB_HOST` | `127.0.0.1` |
| `DB_USER` / `DB_PASS` / `DB_NAME` | Credenciais do app |
| `MYSQL_ROOT_PASSWORD` | Root interno |
| `MYSQL_DATADIR` | `/var/data/mysql` (disco persistente) |
| `ADMIN_PASS` | (opcional) redefine senha do user `admin` no boot |

## Testar no PC (Docker)

```bash
docker build -t ttkpro .
docker run --rm -p 8080:80 -e PORT=80 -v ttkpro-data:/var/data ttkpro
```

## Extensao Chrome

A pasta da extensao **nao** sobe como app no Render. Instale localmente no Chrome se for usar.
