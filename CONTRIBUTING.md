# Contribuindo para Laravel E-commerce Store

Obrigado por seu interesse em contribuir para o Laravel E-commerce Store! Todas as contribuições são bem-vindas, sejam correções de bugs, melhorias de documentação, novos recursos ou relatórios de problemas.

## Como Contribuir

### 1. Fork o Projeto

1. Faça um fork do projeto no GitHub
2. Clone seu fork localmente:
   ```bash
   git clone https://github.com/seu-username/laravel-ecommerce-store.git
   cd laravel-ecommerce-store
   ```

### 2. Configure o Ambiente de Desenvolvimento

1. **Instale as dependências:**
   ```bash
   composer install
   ```

2. **Configure o ambiente:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure o banco de dados:**
   ```bash
   # SQLite (recomendado para desenvolvimento)
   touch database/database.sqlite

   # Ou configure MySQL/PostgreSQL no .env
   ```

4. **Execute as migrations:**
   ```bash
   php artisan migrate
   ```

### 3. Execute os Testes

Antes de fazer alterações, certifique-se de que todos os testes estão passando:

```bash
# Execute todos os testes
php artisan test

# Execute apenas testes unitários
php artisan test --testsuite=Unit

# Execute apenas testes de feature
php artisan test --testsuite=Feature

# Execute com cobertura
php artisan test --coverage
```

### 4. Faça suas Alterações

1. **Crie uma branch para sua feature:**
   ```bash
   git checkout -b feature/nova-funcionalidade
   ```

2. **Siga os padrões de código:**
   - Use PSR-12 para estilo de código
   - Adicione PHPDoc para documentação
   - Mantenha a compatibilidade com versões anteriores
   - Escreva testes para novas funcionalidades

3. **Commit suas mudanças:**
   ```bash
   git add .
   git commit -m "feat: adicionar nova funcionalidade"
   ```

### 5. Push para seu Fork

```bash
git push origin feature/nova-funcionalidade
```

### 6. Abra um Pull Request

1. Vá para a página do repositório original no GitHub
2. Clique em "Compare & pull request"
3. Preencha o template de Pull Request
4. Clique em "Create pull request"

## Padrões de Código

### Convenções de Commits

Use [Conventional Commits](https://conventionalcommits.org/):

```
feat: adicionar nova funcionalidade
fix: corrigir bug
docs: atualizar documentação
style: melhorar estilo de código
refactor: refatorar código
test: adicionar testes
chore: tarefas de manutenção
```

### Estrutura de Código

- **Models**: `app/Models/`
- **Controllers**: `app/Http/Controllers/`
- **Services**: `app/Services/`
- **Traits**: `app/Traits/`
- **Jobs**: `app/Jobs/`
- **Events**: `app/Events/`
- **Listeners**: `app/Listeners/`
- **Notifications**: `app/Notifications/`
- **Mail**: `app/Mail/`
- **Rules**: `app/Rules/`

### Testes

- **Testes Unitários**: `tests/Unit/`
- **Testes de Feature**: `tests/Feature/`
- **Testes de API**: `tests/Feature/Http/Controllers/Api/`

### Documentação

- Use PHPDoc para documentar classes, métodos e propriedades
- Mantenha a documentação do README atualizada
- Adicione exemplos de uso quando relevante

## Relatórios de Problemas

### Antes de Reportar

1. Verifique se o problema já foi reportado
2. Teste com a versão mais recente
3. Inclua informações relevantes:
   - Versão do Laravel
   - Versão do PHP
   - Sistema operacional
   - Passos para reproduzir
   - Comportamento esperado vs atual
   - Logs de erro

### Como Reportar

1. Vá para a seção [Issues](https://github.com/supernova/laravel-ecommerce-store/issues)
2. Clique em "New issue"
3. Escolha o template apropriado
4. Preencha todas as informações solicitadas

## Pull Requests

### Template de Pull Request

```markdown
## Descrição

[Descrição clara e concisa do que foi implementado]

## Tipo de Mudança

- [ ] Bug fix (correção de bug)
- [ ] Nova funcionalidade (new feature)
- [ ] Breaking change (mudança que quebra compatibilidade)
- [ ] Documentação (documentação)
- [ ] Refatoração (refactoring)
- [ ] Melhoria de performance (performance improvement)

## Como Testar

1. [Passo 1]
2. [Passo 2]
3. [Passo 3]

## Screenshots

[Adicione screenshots se relevante]

## Checklist

- [ ] Meu código segue os padrões do projeto
- [ ] Adicionei testes para as mudanças
- [ ] Executei os testes existentes e todos passaram
- [ ] Atualizei a documentação
- [ ] Verifiquei se não há conflitos de merge

## Issues Relacionadas

- Closes #123
- Related to #456
```

### Revisão de Código

- Mantenha um tom construtivo
- Foque no código, não na pessoa
- Sugira melhorias específicas
- Explique o "porquê" das sugestões

## Desenvolvimento

### Ambiente de Desenvolvimento

Recomendamos usar:

- **PHP**: 8.2+
- **Laravel**: 12.0+
- **Banco de dados**: SQLite para desenvolvimento
- **Cache**: Redis (opcional)
- **Queue**: Database ou Redis

### Comandos Úteis

```bash
# Instalar dependências
composer install

# Executar testes
php artisan test

# Executar testes com cobertura
php artisan test --coverage

# Verificar estilo de código
php artisan pint

# Análise estática
php artisan phpstan:analyse

# Gerar documentação
php artisan scribe:generate
```

### Debug

Para ativar debug detalhado:

```php
// Em config/store.php
'debug' => [
    'enabled' => true,
    'log_queries' => true,
    'log_cache' => true,
    'log_emails' => true
]
```

## Licença

Este projeto está licenciado sob a MIT License. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## Agradecimentos

Obrigado por contribuir para o Laravel E-commerce Store! Sua ajuda é muito apreciada. 🚀