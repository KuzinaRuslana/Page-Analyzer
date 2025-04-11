### Hexlet tests and linter status
[![Actions Status](https://github.com/KuzinaRuslana/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/KuzinaRuslana/php-project-9/actions)
### My GitHub actions
[![My check](https://github.com/KuzinaRuslana/php-project-9/actions/workflows/custom-check.yml/badge.svg)](https://github.com/KuzinaRuslana/php-project-9/actions/workflows/custom-check.yml)

### What is Page Analyzer
Page Analyzer is a simple SEO tool that helps you to analyze how websites perform on search engines.

### Requirements
+ PHP version >= 8
+ Composer
+ PostgreSQL

### Installation and usage
1. Create and install the project using Git and Composer:
```bash
git clone https://github.com/KuzinaRuslana/Page-Analyzer.git
cd Page-Analyzer
make install
```
2. Create .env file:
```bash
cp .env.example .env
```
3. Open .env and use your database configs.
4. Run your local server:
```bash
make start
```
5. Woah: the project is now available on http://localhost:8000!