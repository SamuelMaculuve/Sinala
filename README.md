# Sinala

Plataforma SaaS para gestão de eventos, presenças, assinaturas digitais e pagamentos.

## Instalação

```bash
composer install
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

## Credenciais iniciais

As palavras-passe abaixo são temporárias e devem ser alteradas no primeiro acesso.

### Super Administradores

| Nome | E-mail | Palavra-passe inicial |
| --- | --- | --- |
| Samuel Maculuve | `samuelmaculuve8@gmail.com` | `Admin@2026!` |
| K. Massango | `kmassango1@gmail.com` | `Admin@2026!` |
| Edmilson Saiete | `edmilsonsaiete6@gmail.com` | `Admin@2026!` |

### Organização CIES

**CIES - Centro Informazione e Educazione allo Sviluppo**

| Nome | E-mail | Perfil | Palavra-passe inicial |
| --- | --- | --- | --- |
| Leodemila Zacarias | `leodemila.zacarias@gmail.com` | Administrador da Organização | `Cies@2026!` |
| João Gomes | `digit.coordination@cies.it` | Gestor de Eventos | `Cies@2026!` |

## Desenvolvimento

```bash
npm run dev
php artisan test
```
