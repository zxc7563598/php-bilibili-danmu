module.exports = {
  extends: ["@commitlint/config-conventional"],
  rules: {
    "type-enum": [
      2,
      "always",
      [
        "feat",
        "fix",
        "docs",
        "style",
        "refactor",
        "perf",
        "test",
        "build",
        "ci",
        "chore",
        "revert",
        "temp",
        "hotfix",
      ],
    ],
    "subject-empty": [2, "never"],
    "type-empty": [2, "never"],
    "scope-case": [0],
  },
};
