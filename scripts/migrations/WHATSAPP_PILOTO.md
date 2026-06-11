# WhatsApp — Piloto Fase B (Caderno Frutag)

## 1. Banco de dados

```bash
mysql -u ... caderno < scripts/migrations/whatsapp_piloto.sql
```

## 2. Variáveis no servidor (Docker / `.env`)

```env
WHATSAPP_TOKEN=              # Token permanente do app Meta
WHATSAPP_PHONE_NUMBER_ID=    # ID do número WhatsApp Business
WHATSAPP_VERIFY_TOKEN=       # String aleatória (mesma do webhook Meta)
WHATSAPP_APP_SECRET=         # App Secret (validação de assinatura)
OPENAI_API_KEY=              # Já usado pelo assistente de voz
```

## 3. Meta Developer Console

1. Criar app em [developers.facebook.com](https://developers.facebook.com)
2. Adicionar produto **WhatsApp**
3. Webhook URL: `https://caderno.frutag.com.br/funcoes/whatsapp/webhook.php`
4. Verify token: mesmo valor de `WHATSAPP_VERIFY_TOKEN`
5. Assinar o campo **messages**

## 4. Vincular usuário

- Cadastrar o celular em **Propriedade → telefone** no Caderno
- No WhatsApp, enviar **VINCULAR** para o número Frutag
- Ou: primeira mensagem tenta vincular automaticamente se o telefone bater

## 5. Uso

- Áudio ou texto: *"Registrei colheita de alface na estufa 1"*
- Confirmação: responder **SIM** ou **NÃO**
- **AJUDA** — exemplos de comandos

## 6. Arquitetura

```
WhatsApp → webhook.php → handler.php → IaPipeline → ApontamentoExecutor
```

Mesma IA do assiste por voz no site.
