# Fenix Exams

Aplicação Full Stack moderna de sistema de provas online, desenvolvida como desafio técnico. Permite que professores criem provas com questões e alternativas, e que alunos as respondam, acumulando pontuação em rankings individuais e globais via Redis.

---

## Destaques Técnicos

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 11 · PHP 8.4 |
| Frontend | Vue 3 · Vite · Tailwind CSS |
| Banco de dados | PostgreSQL 16 |
| Cache / Rankings | Redis 7 |
| Autenticação | Laravel Sanctum |
| Infraestrutura | Docker · Docker Compose |
| Testes | Pest 4 |
| CI | GitHub Actions |

### Arquitetura

O backend segue uma arquitetura em camadas bem definidas:

- **Controllers** — responsáveis apenas por receber a requisição, delegar e retornar o Resource.
- **DTOs (Data Transfer Objects)** — `CreateExamDTO`, `SubmitExamDTO` e nested DTOs (`QuestionDTO`, `AlternativeDTO`) garantem tipagem forte e desacoplamento entre as camadas.
- **Services** — `ExamService` encapsula a criação de provas em transação atômica; `ExamScoringService` cuida do cálculo de pontuação e atualização dos rankings no Redis.
- **Form Requests** — validação e autorização isoladas do controller.
- **API Resources** — transformação de resposta padronizada.

---

## 🚀 Quick Start

> Pré-requisitos: Docker e Docker Compose instalados.

```bash
cp .env.example .env && docker-compose up -d --build && docker exec fenix_app composer install && docker exec fenix_app php artisan key:generate && docker exec fenix_app php artisan migrate --seed
```

A aplicação estará disponível em **http://localhost:8080**.

---

## 🐳 Containers Docker

| Container | Imagem | Porta | Função |
|---|---|---|---|
| `fenix_app` | Dockerfile local (PHP 8.4-FPM) | `8080` | Aplicação Laravel + Vite dev server (`5173`) |
| `fenix_db` | `postgres:16-alpine` | `5432` | Banco de dados principal |
| `fenix_redis` | `redis:7-alpine` | `6379` | Rankings em tempo real e cache |

---

## 🧪 Qualidade e Testes

O projeto possui **84% de cobertura de código** verificada com Pest e um pipeline de CI configurado no GitHub Actions que valida automaticamente cada push e pull request na branch `main`.

**Rodar os testes com cobertura:**

```bash
docker exec fenix_app php vendor/bin/pest --coverage
```

**Rodar apenas os testes:**

```bash
docker exec fenix_app php vendor/bin/pest
```

---

## 🛠️ Makefile

Para facilitar operações comuns dentro do container:

```bash
make setup
```

Executa `composer install`, `npm install`, `key:generate` e `migrate` de uma vez.
