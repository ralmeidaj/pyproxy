# Especificação de Requisitos — Plataforma Payproxy

## Contexto

A Secretaria Municipal da Fazenda de Salvador (SEFAZ) necessita contratar solução SaaS para
operacionalizar a arrecadação municipal via DDA (Débito Direto Autorizado), com emissão de
boletos registrados, split de pagamento entre múltiplos favorecidos e comunicação digital com
contribuintes.

Este documento especifica os requisitos da plataforma intermediária que expõe uma API para
geração de boletos com split, com suporte a **múltiplos parceiros bancários**, gestão de múltiplos
tenants (aplicações consumidoras) e módulo de relatórios.

---

## 1. Requisitos Funcionais

### 1.1 Gestão de Acesso por Aplicação Consumidora

#### 1.1.1 Ciclo de Vida da Aplicação

**RF-AC-01** O sistema deve gerenciar o ciclo de vida de cada aplicação com os seguintes estados
explícitos: `PENDENTE_APROVAÇÃO → ATIVA → SUSPENSA → INATIVA`.

**RF-AC-02** O cadastro de nova aplicação deve passar por **aprovação explícita** de um
administrador da plataforma antes de ter qualquer acesso liberado.

**RF-AC-03** O administrador pode **suspender temporariamente** uma aplicação, bloqueando
todas as chamadas à API sem revogar configurações ou API keys. A reativação deve restaurar o
estado anterior integralmente.

**RF-AC-04** O administrador pode **inativar definitivamente** uma aplicação, encerrando todos os
acessos. A inativação é irreversível sem novo ciclo de aprovação.

**RF-AC-05** Toda mudança de estado deve registrar: administrador responsável, motivo (campo
texto obrigatório) e timestamp.

**RF-AC-06** Aplicação suspensa ou inativa deve receber resposta `403 Forbidden` com mensagem
informativa em todas as chamadas à API, sem expor detalhes internos da restrição.

**RF-AC-07** O sistema deve manter histórico completo e imutável de mudanças de estado por
aplicação, consultável pelo backoffice.

---

#### 1.1.2 Portal do Tenant — Múltiplos Usuários por Aplicação

**RF-AC-08** A plataforma deve disponibilizar um **portal web por tenant**, separado do backoffice
administrativo interno, onde os usuários da aplicação gerenciam boletos, configurações e
relatórios.

**RF-AC-09** Cada tenant pode ter **múltiplos usuários humanos** com papéis distintos:

| Papel | Capacidades |
|---|---|
| **ADMIN** | Gerenciar usuários do tenant, configurar boleto e splits, visualizar relatórios e auditoria |
| **OPERADOR** | Emitir e cancelar boletos, consultar status, visualizar relatórios |
| **VISUALIZADOR** | Consulta de boletos e relatórios apenas (sem escrita) |

**RF-AC-10** O tenant ADMIN pode convidar, ativar e desativar usuários dentro do seu próprio
tenant. O convite é enviado por e-mail e expira em 48 horas.

**RF-AC-11** Cada usuário de tenant autentica com **e-mail + senha + 2FA** (TOTP) para acessar
o portal.

**RF-AC-12** Um usuário de tenant visualiza e gerencia **somente os dados do seu próprio tenant**
(isolamento estrito entre tenants).

**RF-AC-13** Um tenant ADMIN **não pode conceder permissões além** do que o próprio tenant está
autorizado pela plataforma (sem escalada de privilégios).

**RF-AC-14** O sistema deve registrar na trilha de auditoria todas as ações de gestão de usuários
(convite, ativação, desativação, alteração de papel).

---

#### 1.1.3 Permissões Granulares por API Key

**RF-AC-15** Cada API key deve poder ter um **perfil de permissões granulares** além dos escopos
básicos, configurável pelo administrador da plataforma:

- Valor máximo por boleto (ex.: limitar a R$ 50.000,00 por emissão)
- Limite diário de boletos emitidos
- Limite mensal de boletos emitidos
- Restrição por tipo de tributo (via campo `metadata`, ex.: somente `IPTU` e `ISS`)
- Permissão ou restrição de emissão em lote (batch)

**RF-AC-16** O mesmo tenant pode ter **múltiplas API keys com perfis distintos** (ex.: chave de
produção com acesso total, chave de integração com valor máximo reduzido e sem batch).

**RF-AC-17** As permissões granulares podem ser **modificadas sem revogar a API key**, evitando
interrupção operacional.

**RF-AC-18** Tentativas de operação que excedam as permissões configuradas devem retornar
`403 Forbidden` com mensagem que descreve o tipo de restrição violada, sem revelar os limites
exatos configurados internamente.

**RF-AC-19** O sistema deve alertar o administrador da plataforma quando uma aplicação atingir
80% do seu limite mensal de emissões.

---

### 1.2 Gestão de Tenants (Aplicações Consumidoras)

**RF-01** O sistema deve permitir o cadastro de tenants, representando cada aplicação ou sistema
externo que irá consumir a API de boletos.

**RF-02** Cada tenant deve possuir: nome, documento (CNPJ), status (ativo / suspenso / inativo) e
data de cadastro.

**RF-03** O sistema deve permitir a criação, listagem e revogação de API keys por tenant.

**RF-04** Cada API key deve possuir escopos de permissão granulares:
- `boleto:write` — emitir e cancelar boletos
- `boleto:read` — consultar boletos
- `report:read` — acessar relatórios

**RF-05** A API key completa deve ser exibida **uma única vez** no momento da criação; após isso,
somente o prefixo (primeiros caracteres) é visível para identificação.

**RF-06** O sistema deve suportar data de expiração opcional por API key.

**RF-07** Deve haver controle de rate limiting por API key, configurável por tenant.

---

### 1.3 Gestão de Parceiros Bancários

**RF-PART-01** A plataforma deve suportar o cadastro e operação de **múltiplos parceiros bancários** (fintechs, bancos ou correspondentes bancários autorizados), sem limitação a um único provedor.

**RF-PART-02** Cada parceiro bancário cadastrado deve conter:
- Nome e identificador único na plataforma
- Tipo: `FINTECH`, `BANCO` ou `CORRESPONDENTE_BANCÁRIO`
- Credenciais de integração com a API do parceiro (armazenadas com AES-256-GCM)
- Funcionalidades suportadas: split de pagamento, registro DDA, QR Code Pix, CNAB 240, CNAB 400
- Status: `ATIVO` / `INATIVO`

**RF-PART-03** O administrador da plataforma pode ativar ou desativar um parceiro bancário sem necessidade de redeploy e sem impactar tenants que utilizam outros parceiros.

**RF-PART-04** Cada tenant deve configurar qual parceiro bancário será utilizado para emissão de boletos. Um tenant pode ter configurações para múltiplos parceiros, com um **parceiro padrão** definido.

**RF-PART-05** No momento da emissão de um boleto via API, o tenant pode informar opcionalmente o parceiro bancário a ser utilizado; caso não informado, o parceiro padrão do tenant é aplicado.

**RF-PART-06** A plataforma deve validar, antes da emissão, se o parceiro bancário selecionado suporta as funcionalidades requeridas (split, DDA, Pix), retornando erro claro caso não suporte.

**RF-PART-07** A integração com cada parceiro bancário deve ser encapsulada em um adaptador específico daquele parceiro, de forma que novos parceiros possam ser incorporados sem alteração na API exposta aos tenants.

**RF-PART-08** O resultado da emissão (código de barras, linha digitável, QR Code Pix, confirmação DDA) deve ser normalizado pela plataforma em formato padrão, independentemente do parceiro bancário utilizado.

---

### 1.4 Configuração de Boleto por Tenant

**RF-08** Cada tenant deve ter uma configuração de boleto contendo:
- Referência ao parceiro bancário ativo para o tenant (conforme RF-PART-04)
- Prazo padrão de vencimento (em dias)
- Percentual de multa após vencimento
- Percentual de juros ao mês
- Percentual ou valor de desconto para pagamento antecipado (opcional)
- Dias de antecedência para aplicação do desconto (opcional)
- Linhas de instrução exibidas no boleto (até 2 linhas)
- URL de webhook para notificação de pagamento ao tenant
- Segredo HMAC para assinatura dos webhooks enviados ao tenant

**RF-09** Um tenant pode ter configurações para múltiplos parceiros bancários (ex.: parceiro A para produção, parceiro B para sandbox), com indicação de qual está ativo como padrão.

---

### 1.5 Configuração de Split de Pagamento

**RF-10** Cada tenant deve poder cadastrar múltiplos favorecidos (beneficiários) de split,
associados à sua configuração de boleto.

**RF-11** Cada favorecido do split deve conter:
- Nome/rótulo identificador
- Identificador de credencial do favorecido no parceiro bancário
- Tipo de split: **percentual** (ex.: 85%) ou **valor fixo** (ex.: R$ 10,00)
- Ordem de prioridade de aplicação

**RF-12** O sistema deve validar que:
- A soma dos splits percentuais não ultrapassa 100%
- A soma dos splits de valor fixo não ultrapassa o valor total do boleto
- A configuração combinada (percentual + fixo) resulta em valor positivo para todos os
  favorecidos

**RF-13** O conjunto de splits configurado é aplicado automaticamente a todos os boletos gerados
pelo tenant, salvo configuração específica por boleto.

**RF-14** O snapshot exato dos splits aplicados deve ser registrado junto ao boleto no momento da
emissão, garantindo rastreabilidade histórica mesmo que a configuração seja alterada
posteriormente.

---

### 1.6 Geração de Boletos

**RF-15** O sistema deve expor endpoint para geração individual de boleto, recebendo:
- Referência externa do tenant (identificador no sistema do cliente)
- Dados do pagador: nome, CPF/CNPJ, e-mail, telefone, endereço completo
- Valor do boleto
- Data de vencimento
- Metadados livres (campo JSON para dados adicionais do tenant, ex.: tipo de tributo,
  exercício fiscal, inscrição imobiliária)

**RF-16** O sistema deve expor endpoint para geração em lote (batch), suportando até 500 boletos
por requisição, processados de forma assíncrona.

**RF-17** O tenant deve poder consultar o andamento de um processamento em lote, incluindo
contagem de sucessos e falhas individuais.

**RF-18** O boleto gerado deve conter:
- Código de barras padrão CNAB FEBRABAN
- Linha digitável
- QR Code Pix dinâmico
- URL do boleto em PDF
- Confirmação de registro DDA (quando aplicável)
- Splits aplicados com valor calculado por favorecido

**RF-19** O sistema deve garantir idempotência: boleto com mesma referência externa e mesmo
tenant retorna o boleto existente (se ainda válido) sem criar duplicata.

**RF-20** O sistema deve suportar cancelamento de boleto, desde que ainda não liquidado.

---

### 1.7 DDA (Débito Direto Autorizado)

**RF-21** O registro DDA deve ser realizado automaticamente no momento da emissão do boleto,
por meio da integração com o parceiro bancário ativo, para os pagadores cujo CPF/CNPJ esteja cadastrado no
sistema DDA da FEBRABAN.

**RF-22** O sistema deve registrar e expor a informação de se o boleto está ou não registrado no
DDA.

---

### 1.8 Comunicação com o Contribuinte

**RF-MSG-01** Cada tenant deve configurar o **modelo de comunicação** aplicável, mapeado aos modelos de contratação do Termo de Referência:

| Modelo | Canais | Equivalente TR |
|---|---|---|
| **Modelo 1** | E-mail | Bolepix + DDA + E-mail (Tabela 01 — R$ 1,51/guia liquidada) |
| **Modelo 2** | E-mail + WhatsApp | Bolepix + DDA + E-mail + WhatsApp (Tabela 02 — R$ 2,10/guia liquidada) |

**RF-MSG-02** Ao emitir um boleto, o sistema deve enviar automaticamente ao pagador:
- **E-mail** com PDF do boleto, linha digitável, QR Code Pix e instruções de pagamento (ambos os modelos)
- **Mensagem WhatsApp** com link do boleto e QR Code Pix (somente Modelo 2)

**RF-MSG-03** O endereço de e-mail e o número de telefone utilizados no envio são provenientes dos dados do pagador informados no momento da emissão (RF-15). Campos ausentes não impedem a emissão — apenas o canal correspondente é suprimido, com registro em log.

**RF-MSG-04** O tenant deve poder configurar template de e-mail com logotipo, nome da entidade e texto personalizado. Templates de WhatsApp devem seguir o padrão HSM (Highly Structured Messages) aprovado pela Meta.

**RF-MSG-05** O sistema deve registrar o status de entrega de cada comunicação vinculada ao boleto: `ENVIADO`, `ENTREGUE`, `FALHA`. Falha de entrega não impede nem reverte a emissão do boleto.

**RF-MSG-06** O tenant ADMIN ou OPERADOR pode reenviar manualmente a comunicação para um boleto específico, desde que o boleto esteja com status `PENDENTE`.

---

### 1.9 Conciliação e Notificações de Pagamento

**RF-23** O sistema deve receber notificações (webhooks) do parceiro bancário informando a liquidação de
boletos.

**RF-24** Ao receber notificação de pagamento, o sistema deve:
- Atualizar o status do boleto para "Pago"
- Registrar data/hora, valor pago e canal de pagamento
- Invalidar imediatamente quaisquer outras formas de pagamento vinculadas ao mesmo
  documento (prevenção de duplicidade)

**RF-25** O sistema deve retransmitir a notificação de pagamento para a URL de webhook
cadastrada pelo tenant, assinada com HMAC-SHA256 usando o segredo configurado.

**RF-26** A conciliação deve ser idempotente: notificações duplicadas para boleto já liquidado
devem ser ignoradas sem gerar inconsistência.

**RF-27** Falhas na entrega do webhook ao tenant devem gerar retentativas automáticas com
backoff exponencial (mínimo 3 tentativas).

**RF-28** Um job periódico diário deve identificar boletos com vencimento ultrapassado e status
pendente, atualizá-los para "Expirado" após confirmar o status no parceiro bancário.

**RF-29** O sistema deve executar um **job noturno de conciliação ativa** que identifica boletos
com status Pendente que não receberam notificação de pagamento via webhook e consulta
proativamente o parceiro bancário ativo de cada boleto para verificar a situação real. O job
deve:

- Selecionar boletos com status `PENDENTE` cujo vencimento esteja dentro de uma janela
  configurável (ex.: últimos 30 dias) e que não possuam registro de webhook de pagamento
  recebido do parceiro bancário
- Para cada boleto selecionado, consultar a API do parceiro bancário para obter o status atual
- Caso o boleto esteja **pago** no parceiro bancário:
  - Atualizar o status para `PAGO` na base de dados, registrando data/hora, valor e canal de
    pagamento retornados pelo parceiro bancário
  - Gerar registro de auditoria indicando que a atualização foi realizada pelo job de
    conciliação (ator: `SYSTEM/reconciliation-job`), e não por webhook recebido
  - Acionar a notificação webhook do tenant (conforme RF-25), informando o pagamento com
    os mesmos dados que seriam enviados em caso de notificação normal
- Caso o boleto esteja cancelado ou expirado no parceiro bancário, atualizar o status correspondente
  sem acionar webhook de pagamento
- Processar os boletos em lotes configuráveis para não sobrecarregar a API do parceiro
  bancário nem gerar picos de requisições
- Ser **idempotente**: execuções repetidas não devem gerar duplicidade de notificações nem
  inconsistências na base de dados
- Registrar ao final de cada execução: total de boletos consultados, total atualizados como
  pagos, total com outros status, total de erros de consulta
- Falhas individuais de consulta ao parceiro bancário devem ser registradas em log e não devem
  interromper o processamento dos demais boletos da mesma execução
- O horário e a janela de busca devem ser configuráveis sem necessidade de redeploy

---

### 1.10 Relatórios

**RF-44** O sistema deve fornecer relatório de sumário com os seguintes indicadores, filtráveis por
período:
- Total de boletos emitidos
- Total de boletos liquidados
- Total de boletos cancelados
- Total de boletos expirados
- Volume financeiro emitido
- Volume financeiro liquidado
- Taxa de liquidação (%)
- Ticket médio

**RF-45** O sistema deve fornecer relatório de evolução temporal (diária, semanal ou mensal)
cruzando emissão e liquidação.

**RF-46** O sistema deve fornecer relatório de liquidações por canal de pagamento:
- PIX
- Internet Banking / Autoatendimento
- DDA
- Correspondente Bancário
- Guichê de Caixa
- Débito Automático em Conta

**RF-47** O sistema deve fornecer relatório de liquidações segmentado por campos presentes nos
metadados do boleto (ex.: tipo de tributo, exercício, região administrativa, bairro, zona fiscal).

**RF-48** O sistema deve fornecer relatório de inadimplência, identificando boletos vencidos há
mais de 30, 60 e 90 dias.

**RF-49** O sistema deve permitir exportação dos dados de boletos em formato CSV e JSON,
suportando filtros por período, status, canal e campos de metadados.

**RF-50** Para exportações de grande volume, o processamento deve ser assíncrono, com
disponibilização de URL de download após conclusão.

---

### 1.11 Painel Administrativo (Backoffice)

**RF-36** O sistema deve possuir painel web administrativo para gestão da plataforma, com acesso
restrito a operadores internos.

**RF-37** O painel deve oferecer:
- Gestão de tenants (criação, edição, suspensão)
- Gestão de API keys por tenant
- Configuração de boleto e splits por tenant
- Configuração de credenciais do parceiro bancário por tenant
- Visualização de boletos com filtros avançados
- Dashboards com os relatórios RF-44 ao RF-48
- Visualização da trilha de auditoria

**RF-38** O dashboard deve exibir visualmente, com atualização em tempo real:
- Gráfico de linha de emissão vs. liquidação por período
- Gráfico de distribuição por canal de pagamento
- Gráfico de distribuição por tipo de tributo (via metadados)
- Indicadores de SLA de disponibilidade

---

### 1.12 Trilha de Auditoria

**RF-39** Toda operação de escrita (criação, alteração, cancelamento de boletos; criação e
revogação de API keys; alteração de configurações) deve gerar registro imutável de auditoria
contendo: ator, ação, recurso afetado, timestamp, IP de origem e dados relevantes da operação.

**RF-40** A trilha de auditoria deve ter retenção mínima de 5 anos.

**RF-41** Operadores do backoffice devem poder consultar a trilha de auditoria com filtros por
tenant, tipo de operação, ator e período.

---

### 1.13 Higienização de Dados Cadastrais

**RF-42** O sistema deve validar os dados cadastrais do pagador no momento da emissão do boleto:
- **CPF**: verificação de formato e dígitos verificadores (algoritmo Receita Federal)
- **CNPJ**: verificação de formato e dígitos verificadores
- **CEP**: validação de existência e preenchimento automático de logradouro, bairro, cidade e UF

**RF-43** O sistema deve padronizar os dados de endereço do pagador conforme normas dos Correios (abreviatura de logradouro, formatação de CEP, capitalização), registrando o valor original e o valor padronizado junto ao boleto.

---

## 2. Requisitos Não Funcionais

### 2.1 Disponibilidade e Performance

**RNF-01** A plataforma deve garantir disponibilidade mínima de **99,5%** mensal (SLA).

**RNF-02** A geração de boleto individual deve responder em até **3 segundos** em condições
normais de operação.

**RNF-03** A plataforma deve suportar o volume estimado de **341.289 boletos/mês** (≈ 11.376
boletos/dia) com capacidade de pico superior.

**RNF-04** O processamento em batch deve suportar 500 boletos por requisição de forma
assíncrona.

**RNF-05** Os relatórios de grandes períodos devem ser processados de forma assíncrona para não
impactar a disponibilidade da API.

### 2.2 Segurança

**RNF-06** Todas as credenciais de integração com parceiros bancários devem ser armazenadas criptografadas com AES-256-GCM; nunca em texto simples.

**RNF-07** API keys devem ser armazenadas apenas como hash SHA-256; o valor completo não
deve ser recuperável.

**RNF-08** Toda comunicação deve ser feita sobre HTTPS/TLS 1.3 ou superior.

**RNF-09** Webhooks recebidos dos parceiros bancários devem ter a assinatura HMAC-SHA256 validada antes
de qualquer processamento.

**RNF-10** Webhooks enviados ao tenant devem ser assinados com HMAC-SHA256 usando o
segredo configurado pelo tenant.

**RNF-11** O acesso ao backoffice deve exigir autenticação multifator (2FA).

**RNF-12** O backoffice deve implementar controle de acesso baseado em papéis (RBAC).

**RNF-13** O sistema deve aplicar rate limiting por API key para prevenir abuso.

**RNF-14** Todos os dados pessoais dos pagadores (CPF/CNPJ, e-mail, telefone, endereço) devem
ser tratados em conformidade com a LGPD (Lei nº 13.709/2018), com acesso controlado e
registrado.

### 2.3 Conformidade e Interoperabilidade

**RNF-15** Os boletos devem seguir o padrão CNAB FEBRABAN, com suporte explícito aos layouts **CNAB 240** e **CNAB 400** para liquidação e conciliação bancária.

**RNF-16** A API deve seguir o padrão REST com autenticação via API Key.

**RNF-17** Os formatos de troca de dados suportados devem incluir JSON.

**RNF-18** Todos os dados do município/tenant devem ser armazenados exclusivamente em
servidores localizados em território nacional, em conformidade com a LGPD.

**RNF-19** A infraestrutura de hospedagem deve atender a padrões reconhecidos de segurança
(ISO 27001, ISO 22301, Tier III ou equivalentes).

**RNF-20** Os dados devem ser exportáveis em formatos abertos (CSV, JSON) para garantir portabilidade e independência tecnológica. Ao encerramento do contrato, todos os dados do município devem ser disponibilizados para exportação completa sem ônus adicionais e eliminados dos sistemas da plataforma após confirmação de recebimento pelo contratante.

**RNF-25** Todos os endpoints da API REST devem ser documentados sob o padrão **OpenAPI/Swagger**, com a especificação mantida atualizada e disponível publicamente para as aplicações consumidoras.

### 2.4 Manutenibilidade e Operação

**RNF-21** A plataforma deve ser disponibilizada como SaaS (Software as a Service), sem
necessidade de instalação local pelos clientes.

**RNF-22** O sistema deve ter mecanismo de backup automatizado e redundância operacional.

**RNF-23** O sistema deve expor endpoint de health check para monitoramento de disponibilidade.

**RNF-24** Logs de erros e eventos de sistema devem ser centralizados e consultáveis pela equipe
de operação.

**RNF-26** O suporte técnico à plataforma deve atender aos seguintes níveis de severidade (TR 4.1.27):

| Severidade | Critérios de enquadramento | Tempo de resposta | Tempo de resolução | Cobertura |
|---|---|---|---|---|
| **Alta** | Plataforma totalmente indisponível, falha na comunicação DDA, falha no processo de liquidação | ≤ 1 hora | ≤ 4 horas | 24×7 |
| **Média** | Relatórios indisponíveis, instabilidade intermitente, degradação de performance | ≤ 4 horas | ≤ 12 horas | Horário comercial (8h–18h) |
| **Baixa** | Dúvidas operacionais, ajustes de interface, extração de dados | ≤ 12 horas | ≤ 24 horas | Horário comercial (8h–18h) |

---

## 3. Regras de Negócio

**RN-01 Idempotência na emissão**: Boleto com mesma `referência` e mesmo `tenant` já
existente e válido (status ≠ cancelado e ≠ expirado) não deve gerar novo boleto — o existente
deve ser retornado.

**RN-02 Validação de split no momento da emissão**: Mesmo que a configuração de split tenha
sido validada no cadastro, o sistema deve revalidá-la no momento da geração de cada boleto,
garantindo que os valores calculados sejam consistentes com o valor do boleto.

**RN-03 Cancelamento restrito**: Boleto só pode ser cancelado quando estiver com status
"Pendente". Boletos pagos ou expirados não podem ser cancelados.

**RN-04 Imutabilidade da trilha de auditoria**: Registros de auditoria não podem ser alterados
ou excluídos por nenhum ator, incluindo operadores administrativos.

**RN-05 Remuneração por liquidação**: A cobrança do serviço ao tenant deve ser baseada
exclusivamente em boletos efetivamente liquidados, não em boletos emitidos.

**RN-06 Snapshot de configuração**: Os parâmetros de boleto (multa, juros, desconto,
instruções) e os splits efetivamente aplicados devem ser gravados junto ao boleto no momento
da emissão, de forma que alterações futuras na configuração não alterem boletos já emitidos.

**RN-07 DDA e múltiplos meios de pagamento**: Um mesmo boleto pode ser liquidado por
qualquer canal disponível (código de barras, Pix, DDA). A liquidação por qualquer canal deve
invalidar imediatamente os demais.

**RN-08 Integridade dos splits**: A soma dos valores calculados dos splits nunca deve ultrapassar
o valor total do boleto. O valor residual (não distribuído via splits) permanece na conta
principal do tenant.

**RN-09 Prazo de repasse D+1**: Os valores arrecadados via liquidação de boletos devem ser repassados às contas dos beneficiários (tenant e favorecidos do split) até o próximo dia útil após a data de liquidação (D+1), em conformidade com TR 5.10.

---

## 4. Integrações Externas

| Sistema | Finalidade | Sentido |
|---|---|---|
| Parceiro Bancário (ex.: PJBank, outros) | Emissão, registro, consulta e cancelamento de boletos; registro DDA; webhook de pagamento | Bidirecional |
| Sistema do Tenant (ERP/Tributário) | Consumo da API de boletos; recebimento de webhooks de pagamento | Bidirecional |
| FEBRABAN / Rede Bancária | Liquidação efetiva dos boletos pelos canais de pagamento | Indireto (via parceiro bancário) |

