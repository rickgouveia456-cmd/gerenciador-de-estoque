# 📖 Manual do Usuário — Logi-Prime

> Sistema de Gestão de Almoxarifado para Obras de Construção Civil  
> Versão 1.0 · Stanza Construtora · 2026

---

## Sumário

1. [Acesso ao Sistema](#1-acesso-ao-sistema)
2. [Perfis de Acesso](#2-perfis-de-acesso)
3. [Dashboard](#3-dashboard)
4. [Almoxarifado — Controle de Estoque](#4-almoxarifado--controle-de-estoque)
5. [Requisições de Materiais](#5-requisições-de-materiais)
6. [Módulo EPI](#6-módulo-epi)
7. [Ferramentas](#7-ferramentas)
8. [Kits](#8-kits)
9. [Colaboradores](#9-colaboradores)
10. [Relatórios](#10-relatórios)
11. [Catálogo de Insumos](#11-catálogo-de-insumos)
12. [Administração](#12-administração)
13. [Integrações](#13-integrações)
14. [Perguntas Frequentes](#14-perguntas-frequentes)

---

## 1. Acesso ao Sistema

**URL:** configurada pelo TI da Stanza  
**Login:** fornecido pelo administrador  
**Senha inicial:** deve ser trocada no primeiro acesso

### Primeiro acesso
1. Acesse a URL do sistema
2. Informe login e senha
3. Troque a senha pelo menu do usuário (canto superior direito)

### Esqueci a senha
Entre em contato com o administrador do sistema para redefinição.

### Autenticação em dois fatores (2FA)
Para contas sensíveis, o admin pode ativar 2FA via aplicativo autenticador (Google Authenticator, Authy).

---

## 2. Perfis de Acesso

| Perfil | Quem usa | O que pode fazer |
|---|---|---|
| **Admin** | TI / Gestor do sistema | Acesso total |
| **GGO** | Gestor Geral de Obra | Igual ao admin, limitado à sua cidade |
| **Almoxarife** | Responsável pelo almoxarifado | Gerencia estoque, aprova/entrega requisições |
| **Assistente** | Auxiliar do almoxarife | Movimenta estoque, sem aprovação |
| **Analista** | Visualização e relatórios | Só leitura |
| **Mestre de Obra** | Responsável pela frente | Abre requisições (exceto EPI) |
| **Téc. Segurança** | Técnico de SST | Abre requisições, gerencia EPI e colaboradores |
| **Engenheiro** | Engenheiro de campo | Acesso básico, pode ter permissões extras |

---

## 3. Dashboard

A tela inicial exibe:
- **Almoxarifados** acessíveis ao usuário
- **Total de itens** cadastrados
- **Alertas** — itens abaixo do estoque mínimo
- **Críticos** — itens zerados
- **Previsão de ruptura** — itens que vão zerar nos próximos 15 dias

> 💡 **Dica:** O badge vermelho no sino indica alertas de estoque. Clique em "Alertas de Estoque" na sidebar para ver a lista completa.

---

## 4. Almoxarifado — Controle de Estoque

### 4.1 Visualizar estoque
1. Clique no almoxarifado desejado na sidebar
2. Use a barra de busca para filtrar por nome ou código
3. Use os filtros de categoria (EPI, Elétrica, etc.)

### 4.2 Cadastrar novo item
1. Clique em **+ Novo Item**
2. Preencha: código, nome, unidade, quantidade inicial, estoque mínimo e categoria
3. Para EPIs, informe o número do CA
4. Clique em **Salvar**

> ⚠️ O **estoque mínimo** é fundamental — sem ele o sistema não gera alertas.

### 4.3 Registrar movimentação (entrada/saída)
1. Acesse **Registro de Movimentação** na sidebar
2. Selecione o almoxarifado
3. Escolha o tipo: **Entrada** ou **Saída**
4. Adicione os itens e quantidades
5. Confirme

### 4.4 Importar itens via Excel
1. Na tela do almoxarifado, clique em **Importar**
2. Baixe o modelo Excel clicando em **Modelo**
3. Preencha o arquivo com os itens
4. Faça o upload e selecione o modo:
   - **Adicionar** — só insere itens novos
   - **Atualizar** — soma quantidades aos existentes
   - **Substituir** — substitui saldo (cuidado!)

### 4.5 Exportar estoque
Clique em **Exportar Excel** na tela do almoxarifado para baixar o inventário completo com histórico de movimentações.

---

## 5. Requisições de Materiais

### Fluxo completo

```
Mestre abre requisição (status: Pendente)
        ↓
Almoxarife aprova ou recusa
        ↓
Almoxarife separa e entrega (status: Entregue)
        ↓
Estoque é debitado automaticamente
```

### 5.1 Abrir uma requisição (Mestre / Téc. Segurança)
1. Clique em **Nova Requisição** na sidebar
2. Selecione o almoxarifado e informe o colaborador
3. Adicione os itens e quantidades
4. Clique em **Enviar Requisição**
5. Um protocolo será gerado (ex: `REQ-20260901-0001`)

### 5.2 Aprovar/recusar uma requisição (Almoxarife)
1. Acesse **Requisições** na sidebar
2. Clique na requisição com status **Pendente**
3. Revise os itens e quantidades disponíveis
4. Clique em **Aprovar** ou **Recusar** (com motivo)
5. É possível aprovação parcial — item a item

### 5.3 Confirmar entrega (Almoxarife)
1. Na requisição aprovada, clique em **Registrar Entrega**
2. O sistema debita o estoque automaticamente
3. Tire uma foto do comprovante se necessário

---

## 6. Módulo EPI

### 6.1 Ficha de EPI por colaborador
Cada colaborador tem uma **ficha digital** que registra todos os EPIs entregues e devolvidos.

**Criar/abrir ficha:**
1. Acesse **Módulo EPI → Fichas de Entrega**
2. Clique em **Nova Ficha**
3. Informe o colaborador (o sistema verifica se já existe ficha ativa)
4. Adicione os EPIs entregues: descrição, CA, quantidade, tamanho e data

> 📋 Se o colaborador já tem ficha ativa, os novos EPIs são adicionados à ficha existente.

### 6.2 Registrar devolução de EPI
1. Na ficha do colaborador, clique em **Devolver** no item
2. A data de devolução é registrada automaticamente

### 6.3 Matriz de EPI
Define quais EPIs são obrigatórios por função/cargo:
1. Acesse **Módulo EPI → Matriz**
2. Crie uma matriz por função (ex: Pedreiro, Eletricista)
3. Liste os EPIs obrigatórios conforme as NRs aplicáveis

---

## 7. Ferramentas

### Registrar uso de ferramenta
1. Acesse **Ferramentas** no almoxarifado
2. Localize a ferramenta e clique em **Registrar Uso**
3. Informe o colaborador responsável
4. Para devolução, clique em **Devolver**

### Registrar manutenção
1. Na ferramenta, clique em **Manutenção**
2. Descreva o problema e envie para manutenção
3. O status muda para "Em manutenção" automaticamente

---

## 8. Kits

Kits são conjuntos predefinidos de itens para retirada rápida.

**Exemplo:** Kit de Segurança Individual = Capacete + Luvas + Óculos

1. Acesse o almoxarifado → **Kits**
2. Clique em **Novo Kit** e adicione os itens
3. Para retirar um kit completo, use a tela de Movimentação em Lote

---

## 9. Colaboradores

### Cadastrar colaborador
1. Acesse **Colaboradores** na sidebar
2. Clique em **Novo Colaborador**
3. Preencha: nome, função, frente de obra (escopo), obra e cidade
4. Selecione o tipo: Funcionário, Mestre, Engenheiro ou Téc. Segurança

### Tipos e permissões no sistema
- **Funcionário** — retira material com autorização do mestre
- **Mestre** — faz requisições (exceto EPIs)
- **Tec. Segurança** — requisita qualquer item incluindo EPIs
- **Engenheiro** — aparece no autocomplete das requisições

---

## 10. Relatórios

| Relatório | O que mostra | Quem acessa |
|---|---|---|
| **Alertas de Estoque** | Itens zerados e abaixo do mínimo, previsão de ruptura | Todos |
| **Consumo por Período** | Entradas e saídas no período selecionado | Admin, Almoxarife, Analista |
| **Consumo por Pessoa** | Quanto cada colaborador retirou | Admin, Almoxarife, Analista |
| **Ficha EPI** | Histórico de entregas de EPIs | Admin, Téc. Segurança |

Todos os relatórios podem ser **exportados em Excel**.

---

## 11. Catálogo de Insumos

O catálogo é a base de referência de materiais padronizados da empresa.

- **Diferença do estoque:** o catálogo é global; o estoque é por almoxarifado
- Itens do catálogo podem ter **valor unitário** de referência
- A sincronização catálogo ↔ estoque atualiza preços e descrições

**Importar catálogo:**
1. Acesse **Catálogo de Insumos**
2. Clique em **Importar CSV**
3. Formato: `codigo;nome;unidade;categoria;valor_unitario`

---

## 12. Administração

> Apenas perfil **Admin** e **GGO**

### Configuração do Sistema
Acesse **Admin → Configurações** para definir:
- Nome e dados da empresa
- Logo (aparece nos relatórios)
- Versão do sistema

### Saúde do Sistema
Acesse **Admin → Saúde do Sistema** para monitorar:
- Status do banco de dados
- Itens críticos e alertas
- Requisições pendentes
- Atividade das últimas 24h
- Erros de integração

### Backup
Acesse **Admin → Backup** para exportar todos os dados em Excel.

> 🔒 Recomenda-se realizar backup semanal antes de atualizações.

### Transferência de itens entre almoxarifados
1. Na tela do almoxarifado, selecione os itens
2. Clique em **Transferir / Excluir**
3. Selecione o almoxarifado de destino
4. Confirme a transferência

---

## 13. Integrações

### Sienge (ERP)
A integração com o Sienge está em preparação (previsão 2027).

**Quando disponível permitirá:**
- Sincronização automática de materiais/catálogo
- Envio de requisições aprovadas como solicitações de compra
- Importação de saldo de estoque do Sienge

**Para configurar (quando liberado pelo TI):**
1. Acesse **Admin → Integrações**
2. Preencha URL da API, Token e ID da Empresa
3. Ative a integração
4. Clique em **Testar Conexão**

---

## 14. Perguntas Frequentes

**O sistema debitou o estoque errado. Como corrigir?**  
Acesse o item → registre uma **Entrada** de correção com a observação "Ajuste de inventário".

**Como faço para um usuário acessar um almoxarifado diferente do dele?**  
Admin → Usuários → Editar usuário → Conceder Acesso Extra (com prazo opcional).

**O sistema não deixa o mestre requisitar um EPI. Por quê?**  
Por padrão, mestres não podem requisitar itens da categoria EPI. Apenas Téc. Segurança pode. Se necessário, o admin pode alterar.

**Como resetar a senha de um usuário?**  
Admin → Usuários → Editar → preencha o campo Senha e salve.

**Como ativar o tour de ajuda novamente?**  
Clique no botão **Tour** (ícone ?) no canto superior direito da tela.

**Os dados são salvos em tempo real?**  
Sim. Cada ação (movimentação, requisição, entrega) é gravada imediatamente no banco de dados.

---

> 📞 **Suporte técnico:** Henrique Carvalho — rickgouveia157@gmail.com  
> 🏗️ Logi-Prime · Stanza Construtora · Salvador, BA · 2026
