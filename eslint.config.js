import prettier from 'eslint-config-prettier';
import vue from 'eslint-plugin-vue';

import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    {
        ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr', 'tailwind.config.js', 'resources/js/components/ui/*'],
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
            'no-restricted-syntax': [
                'error',
                {
                    selector:
                        "ObjectExpression:has(Property[key.name='label']):has(Property[key.name='value'][value.type='Literal'][value.value=''])",
                    message:
                        "Select options must not have an empty string value (value: ''). Use a semantic value like 'all' or 'default' instead to prevent Reka UI runtime errors.",
                },
            ],
        },
    },
    prettier,
);
