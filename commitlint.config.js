/**
 * Commitlint конфиг для монорепо Systo.
 *
 * Правила построены на @commitlint/config-conventional (Conventional Commits)
 * с настройкой под scope из CONVENTIONS.md проекта.
 *
 * Severity:
 *   2 — error (блокирует commit)
 *   1 — warning (только уведомление)
 *   0 — disabled
 *
 * Сейчас режим МЯГКИЙ — все правила warning (1), коммит проходит.
 * При желании ужесточить — поменять на 2.
 */
module.exports = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    // Тип коммита обязателен и из списка
    'type-enum': [
      1,
      'always',
      [
        'feat',     // новая функциональность
        'fix',      // исправление бага
        'refactor', // рефакторинг без изменения поведения
        'docs',     // изменение документации
        'style',    // форматирование, без изменения логики
        'test',     // добавление/изменение тестов
        'chore',    // рутинные задачи, зависимости
        'perf',     // оптимизация производительности
        'ci',       // CI/CD пайплайны
        'build',    // система сборки, Makefile, Dockerfile, deps
        'revert',   // откат коммита
      ],
    ],
    'type-case': [1, 'always', 'lower-case'],
    'type-empty': [1, 'never'],

    // Scope желателен, но не строгий список (можем встретить новые модули)
    'scope-case': [1, 'always', 'lower-case'],
    'scope-empty': [0],

    // Subject (что после type(scope): )
    'subject-empty': [1, 'never'],
    'subject-case': [
      0,
      'never',
      ['sentence-case', 'start-case', 'pascal-case', 'upper-case'],
    ],
    'subject-full-stop': [1, 'never', '.'],

    // Header (вся первая строка)
    'header-max-length': [1, 'always', 120],

    // Body
    'body-leading-blank': [1, 'always'],
    'body-max-line-length': [1, 'always', 200],

    // Footer
    'footer-leading-blank': [1, 'always'],
    'footer-max-line-length': [1, 'always', 200],
  },
};
