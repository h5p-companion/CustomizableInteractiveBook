# H5P Interactive Book (PT-BR)

Interactive Book e uma biblioteca H5P que organiza diferentes tipos de conteudo em paginas e secoes dentro de um livro interativo. Ela suporta conteudo aninhado, configuracao individual por item e reordenacao via arrastar e soltar no editor.

## Status do projeto
Beta

## Badges
Em alguns READMEs voce pode ver pequenos selos que indicam metadados do projeto, como status de testes. Use o Shields para gerar badges quando fizer sentido.

## Visuais
![Example](./img/exampleCIB.png)

## Instalacao
Dentro do ecossistema H5P, instale as dependencias do projeto e use o conteudo no seu ambiente H5P. Se voce precisa de versoes especificas de Node ou outras dependencias, inclua um bloco de requisitos na sua documentacao interna.

## Build
```bash
npm install
npm run build
```

## Uso
Crie um novo conteudo do tipo Interactive Book no editor H5P, adicione as paginas desejadas e organize as secoes. Cada pagina pode conter conteudo H5P diverso, e o livro cuida da navegacao e do progresso.

## Bloqueio de capitulos por URL
O bloqueio e apenas visual e de navegacao. Ele nao remove o payload do conteudo nem impede que os dados sejam carregados pelo H5P.

- As regras sao avaliadas pelo `pathname + query` da pagina em que o livro esta embutido.
- Todas as regras que casarem sao aplicadas; se duas regras bloquearem o mesmo capitulo, a ultima regra que casar vence.
- Os capitulos sao numerados a partir de 1 (1-based).

### Como configurar
Em `behaviour.lockRules`, adicione uma lista de regras com:

- `enabled` (true/false)
- `matchType`: `contains`, `startsWith` ou `regex`
- `pathPattern`: texto comparado com `pathname + query`
- `lockedChapters`: lista de numeros de capitulos (1-based)
- `lockedText`: texto opcional exibido no capitulo bloqueado

### Exemplos
**Contains**
- `matchType`: `contains`
- `pathPattern`: `/turma-a`
- Resultado: bloqueia quando o path contiver `/turma-a`

**Starts with**
- `matchType`: `startsWith`
- `pathPattern`: `/curso/introducao`
- Resultado: bloqueia quando o path comecar com `/curso/introducao`

**Regex**
- `matchType`: `regex`
- `pathPattern`: `^/curso/(basico|avancado)(\?.*)?$`
- Resultado: bloqueia em qualquer `/curso/basico` ou `/curso/avancado`, com ou sem query

## Suporte
Por sua conta e risco.

## Roadmap
Sem planos de evolucao no momento.

## Contribuicao
Faca um fork do projeto e envie um PR.

## Autores e agradecimentos
- Luiz Gustavo
- KelsonCM @ github

## Licenca
Texto original da licenca MIT:

(The MIT License)

Copyright (c) 2012-2014 Joubel AS

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
