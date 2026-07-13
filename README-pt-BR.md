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

## Arquitetura da política de acesso

O acesso segue uma cadeia clara de responsabilidade: **Moodle → política de acesso → H5P**. O plugin Moodle avalia cursos, grupos, notas e atividades. O Livro Interativo não conhece esses conceitos; ele apenas aplica uma política indexada pelo `subContentId` permanente de cada capítulo.

Antes de criar os runtimes dos capítulos, o livro envia seu manifesto para a janela `parent` imediata:

```json
{
  "type": "h5p-customizable-interactive-book:ready",
  "contractVersion": 1,
  "requestId": "...",
  "contentId": "...",
  "library": "H5P.CustomizableInteractiveBook",
  "chapters": [{ "id": "...", "title": "...", "position": 0, "stable": true }]
}
```

A plataforma pode responder com `h5p-customizable-interactive-book:policy`, usando a mesma versão do contrato, `requestId` e `contentId`, além de um mapa de capítulos com `available` e uma mensagem opcional em texto simples. A origem e a janela remetente são validadas contra o `parent` imediato. A mensagem de prontidão é reenviada durante uma janela curta.

```json
{
  "type": "h5p-customizable-interactive-book:policy",
  "contractVersion": 1,
  "requestId": "...",
  "contentId": "...",
  "required": true,
  "teacherBypass": false,
  "chapters": {
    "uuid-do-capitulo": { "available": false, "message": "Conclua o pré-requisito." }
  }
}
```

Se o livro não estiver embutido, a origem do `parent` não puder ser determinada ou nenhuma política válida chegar em aproximadamente 2,5 segundos, é aplicada uma política allow-all. Assim, o conteúdo continua funcionando fora do Moodle.

O bloqueio é pedagógico e de navegação, não um mecanismo de criptografia do conteúdo. O pacote H5P continua contendo os parâmetros de todos os capítulos. Entretanto, a biblioteca H5P filha de um capítulo bloqueado não é inicializada; esse capítulo não participa de pontuação, progresso, alterações de estado, conclusão, reset, soluções, resumo ou xAPI, e somente um placeholder acessível em texto simples é exibido.

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
