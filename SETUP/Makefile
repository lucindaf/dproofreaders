.PHONY: all less lint tests

SELF_DIR := $(dir $(lastword $(MAKEFILE_LIST)))

all: less lint

#----------------------------------------------------------------------------
# PHP file linting
lint:
	$(SELF_DIR)lint_php_files.sh $(SELF_DIR)..

#----------------------------------------------------------------------------
# CSS compilation
LESSC := lessc
SELF_DIR := $(dir $(lastword $(MAKEFILE_LIST)))
STYLE_DIR := $(SELF_DIR)../styles

BASE_CSS := $(wildcard $(STYLE_DIR)/*.css)
THEME_CSS := $(wildcard $(STYLE_DIR)/themes/*.css)

all: less lint

less: $(BASE_CSS) $(THEME_CSS)

$(THEME_CSS): $(STYLE_DIR)/themes/theme.less

%.css: %.less $(STYLE_DIR)/global.less
	$(LESSC) $< $@

#----------------------------------------------------------------------------
# Unit tests
tests:
	cd $(SELF_DIR)/tests && phpunit --bootstrap phpunit_bootstrap.php --verbose .
