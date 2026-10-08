# H5P.CustomizableInteractiveBook

`H5P.CustomizableInteractiveBook` é uma variante do Livro Interativo com uma camada de acesso a capítulos controlada pela plataforma hospedeira. A plataforma decide quais capítulos estão disponíveis; a biblioteca H5P recebe uma política versionada e a aplica à interface, navegação, estado, pontuação, progresso, conclusão, resumo e xAPI.

A biblioteca é independente da plataforma. Ela não conhece cursos, grupos, notas, conclusões, banco de dados, URLs internas ou classes PHP do Moodle. A implementação hospedeira correspondente é o plugin `local_h5pchapteraccess`.

## Estrutura do repositório

- `src/`: código e metadados compiláveis da biblioteca H5P;
- `src/src/scripts/access/`: manifesto, política, controlador, ponte e helpers de runtime;
- `src/src/scripts/`: aplicação e componentes visuais do Livro Interativo;
- `src/src/styles/`: fontes SCSS;
- `src/tests/`: testes Node;
- `local/h5pchapteraccess/`: plugin Moodle 4.5;
- `docs/architecture.md`: arquitetura completa e fronteiras de confiança;
- `docs/test-plan.md`: matriz de testes automatizados e manuais.

## Manifesto de capítulos

O manifesto é criado a partir de `config.chapters` antes da construção das instâncias filhas. Ele é imutável e contém apenas:

```json
[
  {
    "id": "15f0a80e-4ba1-4b51-9c4a-113e986474a8",
    "title": "Introdução",
    "position": 0,
    "stable": true
  }
]
```

`id` é o `subContentId`, nunca a posição numérica. Um conteúdo legado sem `subContentId` recebe somente em runtime um ID `legacy-position-N` e `stable: false`. IDs duplicados interrompem a inicialização para impedir associação ambígua. O título é apenas metadado de apresentação.

## AccessPolicy

`AccessPolicy` normaliza a resposta externa em um mapa imutável. A política possui `contractVersion`, `required`, `teacherBypass` e, para cada ID, `available` e `message`. Valores opcionais inválidos são normalizados com segurança. Capítulos omitidos permanecem disponíveis. `AccessPolicy.allowAll(manifest)` implementa o fallback autônomo.

## AccessController

`AccessController` é a API de domínio consumida pela interface. Ele informa disponibilidade, mensagem, IDs e quantidade disponíveis, posição visível e próximo/anterior disponível. Os componentes visuais não interpretam diretamente a resposta da plataforma.

## HostBridge

`HostBridge` é o único componente H5P que conversa com a janela `parent`. Ele:

- cria `requestId` com `crypto.randomUUID()`, `crypto.getRandomValues()` ou fallback de runtime;
- deriva a origem exata do `parent` a partir de `document.referrer`;
- envia `ready` apenas para essa origem;
- repete o envio por uma janela curta;
- valida `event.source`, origem, tipo, versão, `requestId` e `contentId`;
- remove listeners e timers no sucesso, timeout ou `dispose()`;
- usa `allowAll` após aproximadamente 2,5 segundos sem resposta válida.

Não existe `targetOrigin: "*"`.

## Contrato postMessage versão 1

Mensagem enviada pelo H5P:

```json
{
  "type": "h5p-customizable-interactive-book:ready",
  "contractVersion": 1,
  "requestId": "7de9f73b-eef1-4d6a-a582-d291c9834894",
  "contentId": "123",
  "library": "H5P.CustomizableInteractiveBook",
  "chapters": [
    {
      "id": "15f0a80e-4ba1-4b51-9c4a-113e986474a8",
      "title": "Introdução",
      "position": 0,
      "stable": true
    }
  ]
}
```

Resposta da plataforma para a origem e janela verificadas:

```json
{
  "type": "h5p-customizable-interactive-book:policy",
  "contractVersion": 1,
  "requestId": "7de9f73b-eef1-4d6a-a582-d291c9834894",
  "contentId": "123",
  "required": true,
  "teacherBypass": false,
  "chapters": {
    "15f0a80e-4ba1-4b51-9c4a-113e986474a8": {
      "available": false,
      "message": "Conclua a atividade pré-requisito."
    }
  }
}
```

Os campos diferenciam maiúsculas de minúsculas. A resposta deve repetir exatamente o `requestId` e o `contentId` textual. Mensagens são texto simples. Regras Moodle e registros internos não fazem parte do contrato.

## Fluxo de inicialização

1. O construtor sanitiza a configuração e cria o manifesto.
2. `HostBridge.requestPolicy()` inicia antes de `PageContent` ou qualquer runtime filho.
3. `attach()` pode mostrar um status acessível de carregamento.
4. A política válida ou o timeout cria o `AccessController`.
5. `initializeRuntime()` executa exatamente uma vez e cria capa, páginas, menu lateral e barras de status.
6. Capítulos disponíveis criam a instância filha com `H5P.newRunnable`.
7. Capítulos bloqueados criam somente o placeholder acessível.

Fora de iframe, sem origem verificável, sem o plugin Moodle ou após resposta inválida, todos os capítulos ficam disponíveis depois do timeout.

## Comportamento de capítulos bloqueados

Um capítulo bloqueado continua focável e selecionável no menu para que a explicação possa ser lida. O placeholder usa estrutura acessível e insere a mensagem externa com `textContent`. Sem capítulo salvo ou link direto, o livro abre no primeiro capítulo disponível. Um capítulo salvo anteriormente só é restaurado se continuar disponível; caso contrário, o livro inicia no primeiro disponível. Links diretos e cliques no menu ainda podem abrir o placeholder de um capítulo bloqueado.

Para um capítulo bloqueado:

- `H5P.newRunnable` não é executado;
- nenhuma biblioteca filha é inicializada e nenhum evento é propagado;
- `instance` permanece `null` e `sections` permanece vazio;
- próximo/anterior ignoram o capítulo;
- menu ou link direto mostram somente o placeholder;
- pontuação, máximo, resposta, progresso, conclusão, resumo, reset, soluções, estado e xAPI o ignoram.

Se todos estiverem bloqueados, o primeiro placeholder é mostrado, o resumo não é criado, as pontuações são zero e o livro não é concluído automaticamente.

## Estado por UUID

O estado atual usa primeiro `chaptersById`, indexado pelo `subContentId`:

```json
{
  "chaptersById": {
    "15f0a80e-4ba1-4b51-9c4a-113e986474a8": {
      "completed": false,
      "tasksLeft": 1,
      "sections": [],
      "state": {}
    }
  },
  "chapters": [],
  "score": 0,
  "maxScore": 0,
  "urlFragments": {}
}
```

O array legado `chapters` é somente fallback de compatibilidade. Um capítulo com UUID nunca recebe estado de outra posição após reorganização. O estado já salvo para um UUID atualmente bloqueado é preservado para restauração em um desbloqueio futuro.

## Pontuação, progresso, conclusão, resumo e xAPI

Somente capítulos disponíveis, não-resumo e com instância válida participam de pontuação, máximo, resposta, estado, soluções, reset, xAPI e conclusão. Conclusão significa que todos os capítulos disponíveis, exceto o resumo, foram concluídos. A barra de progresso conta apenas capítulos disponíveis e mostra texto de bloqueio quando um placeholder é aberto intencionalmente.

O resumo só é criado quando habilitado e existe ao menos um capítulo disponível. Ele não recebe tarefas bloqueadas. Eventos xAPI por capítulo e a submissão final ignoram capítulos bloqueados ou sem instância.

## Build

Requer Node.js compatível e npm:

```bash
cd src
npm ci
npm run build
```

Os artefatos são gerados em `src/dist/`. Não edite arquivos gerados manualmente. `node_modules/` é somente uma dependência de desenvolvimento e não deve integrar o pacote H5P distribuído.

Modo de desenvolvimento:

```bash
npm run dev
npm run watch
```

## Testes e lint

```bash
cd src
npm test
npm run lint
npm run build
```

A suíte cobre manifesto e imutabilidade, UUID estável, fallback legado, duplicidade, normalização, navegação disponível, todos bloqueados, validação das mensagens, timeout, descarte, duplicidade de resposta, prevenção de runtime bloqueado, pontuação e estado por UUID. A matriz completa está em [docs/test-plan.md](docs/test-plan.md).

## Limites de segurança

O Moodle não confia no manifesto enviado pelo navegador: o pacote implantado é extraído novamente no servidor e somente a decisão final por capítulo é enviada. O H5P valida o `parent` imediato, mas não autentica o significado pedagógico da política além da origem e dos campos de correlação. O fallback sem resposta é propositalmente `allowAll` para preservar o uso autônomo.

**O bloqueio controla visualização, inicialização das bibliotecas filhas e navegação, mas o pacote H5P continua contendo os parâmetros originais dos capítulos. A solução não deve ser apresentada como mecanismo de proteção para informações confidenciais.**

Conteúdo confidencial exige autorização no servidor e recursos protegidos separados.

## Licença

MIT. Consulte [LICENSE](src/LICENSE).
