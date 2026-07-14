# Plano de testes da integração

## Convenções

Considere um livro com capítulos `A`, `B` e `C`, identificados por UUID. `S(X)` e `M(X)` representam score e score máximo do capítulo `X`. “Progresso 2/2” significa dois capítulos disponíveis no denominador, independentemente das posições originais.

O campo **Resultado obtido** distingue validações automatizadas já executadas de cenários que exigem Moodle com banco, H5P real e navegador. Uma pendência ambiental não altera a implementação esperada.

## Matriz funcional

| Cenário | Perfil | Condição | Capítulos esperados | Pontuação esperada | Progresso esperado | Mensagem esperada | Resultado obtido |
|---|---|---|---|---|---|---|---|
| Todos disponíveis | Estudante | A/B/C `open` | A, B e C executáveis | `S(A)+S(B)+S(C)` / `M(A)+M(B)+M(C)` | 0/3 até 3/3 | Nenhuma | Coberto por testes unitários; integração manual pendente |
| Primeiro bloqueado | Estudante | A `locked` | A placeholder; B e C executáveis | `S(B)+S(C)` / `M(B)+M(C)` | 0/2 até 2/2 | Específica de A ou fallback | Coberto por testes de política/runtime; manual pendente |
| Intermediário bloqueado | Estudante | B `locked` | A e C executáveis; B placeholder | `S(A)+S(C)` / `M(A)+M(C)` | 0/2 até 2/2 | Mensagem efetiva de B | Coberto por navegação e score unitários; manual pendente |
| Último bloqueado | Estudante | C `locked` | A e B executáveis; C placeholder | `S(A)+S(B)` / `M(A)+M(B)` | 0/2 até 2/2 | Mensagem efetiva de C | Coberto por testes unitários; manual pendente |
| Todos bloqueados | Estudante | A/B/C `locked` | Somente placeholders; nenhum runtime filho | 0 / 0 | Estado textual, sem incremento | Mensagem do placeholder selecionado | Teste JS automatizado aprovado; manual pendente |
| Menu em bloqueado | Estudante | B `locked` | Clique/Enter abre somente B placeholder | Sem mudança | Sem mudança | Mensagem de B e descrição acessível | Revisão estática concluída; navegador pendente |
| Próximo/anterior | Estudante | B `locked` | A → próximo C; C → anterior A | Somente A e C | Denominador 2 | Nenhuma durante salto | Teste JS automatizado aprovado |
| Hash bloqueado | Estudante | Hash de B bloqueado | B placeholder, nunca conteúdo real | Sem B | Sem incremento | Mensagem de B | Revisão de fluxo concluída; navegador pendente |
| Estado por UUID | Estudante | Reorganizar C/A/B | Estado volta ao mesmo UUID | Mantido por UUID disponível | Mantido por UUID | Conforme política atual | Teste JS automatizado aprovado |
| Estado de bloqueado preservado | Estudante | B tinha estado e passou a `locked` | Estado de B não é aplicado a outro capítulo | B excluído do score atual | B excluído | Mensagem de B | Coberto por estrutura de estado; integração pendente |
| Resumo parcial | Estudante | B `locked`, resumo habilitado | Resumo contém A e C | Soma de A e C | Denominador 2 | Nenhuma de B no resumo | Revisão/cobertura de agregação; navegador pendente |
| Reset e soluções | Estudante | B `locked` | Chamadas somente em A e C válidos | Recalculado sem B | Recalculado sem B | Nenhuma | Revisão de guardas concluída; navegador pendente |
| xAPI | Estudante | B `locked` | Eventos/agregação somente A e C | Resultado sem B | Conclusão sem B | Nenhuma | Revisão de guardas concluída; captura xAPI pendente |
| Sem plugin Moodle | Qualquer | Nenhum listener responde | A, B e C após timeout | Todos os capítulos | Denominador 3 | Nenhuma | Teste JS de timeout aprovado |

## Matriz de Availability API e perfis

| Cenário | Perfil | Condição | Capítulos esperados | Pontuação esperada | Progresso esperado | Mensagem esperada | Resultado obtido |
|---|---|---|---|---|---|---|---|
| Data futura | Estudante | B após data futura | A e C; B bloqueado | A + C | 0/2 até 2/2 | Explicação da data ou fallback | PHPUnit escrito; execução pendente por ambiente |
| Data passada | Estudante | B após data já atingida | A, B e C | Todos | 0/3 até 3/3 | Nenhuma | PHPUnit escrito; execução pendente por ambiente |
| Grupo membro | Estudante no grupo | B exige grupo | A, B e C | Todos | Denominador 3 | Nenhuma | PHPUnit escrito; execução pendente por ambiente |
| Grupo não membro | Estudante fora do grupo | B exige grupo | A e C | A + C | Denominador 2 | Grupo ou fallback | PHPUnit escrito; execução pendente por ambiente |
| Conclusão anterior | Estudante | B exige outra atividade concluída | B conforme conclusão | Apenas disponíveis | Apenas disponíveis | Condição ou fallback | PHPUnit escrito; execução pendente por ambiente |
| Nota | Estudante | B exige faixa de nota | B conforme nota atual | Apenas disponíveis | Apenas disponíveis | Condição ou fallback | PHPUnit escrito; execução pendente por ambiente |
| AND | Estudante | Data e grupo | B somente se ambas verdadeiras | Apenas disponíveis | Apenas disponíveis | Informação combinada | PHPUnit escrito; execução pendente por ambiente |
| OR | Estudante | Data ou grupo | B se uma for verdadeira | Apenas disponíveis | Apenas disponíveis | Informação combinada | PHPUnit escrito; execução pendente por ambiente |
| Restrição oculta | Estudante | B bloqueado e `showrestriction=0` | A e C | A + C | Denominador 2 | Específica, padrão ou genérica; sem detalhes | PHPUnit escrito; execução pendente por ambiente |
| Bypass | Professor editor | Qualquer regra | A, B e C disponíveis | Todos | Denominador 3 | Nenhuma; `teacherBypass=true` | PHPUnit escrito e caminho otimizado; execução pendente |
| Políticas diferentes | Dois estudantes | Grupo/nota diferentes | Resultado individual | Soma individual | Denominador individual | Mensagem individual | PHPUnit escrito; execução pendente por ambiente |

## Matriz de comunicação e segurança

| Cenário | Perfil | Condição | Capítulos esperados | Pontuação esperada | Progresso esperado | Mensagem esperada | Resultado obtido |
|---|---|---|---|---|---|---|---|
| Origem inválida | Atacante/outro frame | `event.origin` diferente | Mensagem ignorada; fallback posterior | Todos pelo fallback | Todos pelo fallback | Nenhuma | Testes Node H5P e AMD aprovados |
| Source inválido | Outro iframe | `contentWindow` diferente | Mensagem ignorada | Sem alteração | Sem alteração | Nenhuma | Testes Node aprovados |
| `requestId` inválido/duplicado | Mesmo iframe | Correlação errada ou repetida | Sem segunda resposta | Sem alteração | Sem alteração | Nenhuma | Testes Node aprovados |
| `contentId` incompatível | Mesmo iframe | Conteúdo não pertence ao cmid | Serviço rejeita; fallback H5P | Todos pelo fallback | Todos pelo fallback | Nenhum detalhe técnico | Testes Node e PHPUnit escritos |
| Contrato/tipo inválido | Mesmo iframe | Versão ou tipo diferente | Mensagem ignorada | Sem alteração | Sem alteração | Nenhuma | Testes Node aprovados |
| Falha AJAX | Estudante | Erro de rede/servidor | Fallback após timeout | Todos | Denominador completo | Nenhum stack trace | Teste de timeout aprovado; rede manual pendente |
| Consulta sem login | Anônimo | AJAX direto | Acesso negado | Não aplicável | Não aplicável | Erro Moodle controlado | PHPUnit escrito; execução pendente |
| Outro curso | Estudante sem acesso | `cmid` externo | Acesso negado | Não aplicável | Não aplicável | Erro Moodle controlado | PHPUnit escrito; execução pendente |

## Matriz de ciclo de vida

| Cenário | Perfil | Condição | Capítulos esperados | Pontuação esperada | Progresso esperado | Mensagem esperada | Resultado obtido |
|---|---|---|---|---|---|---|---|
| Adicionar capítulo | Professor | Novo UUID D | D criado como `open` | Inclui D | Denominador +1 | Nenhuma | PHPUnit de sincronização escrito |
| Remover capítulo | Professor | B ausente no pacote | B `active=0`, regra preservada | Sem B | Sem B | Não aplicável | PHPUnit escrito |
| Reintroduzir capítulo | Professor | Mesmo UUID B retorna | B reativado com regra anterior | Conforme regra B | Conforme regra B | Mensagem anterior | PHPUnit escrito |
| Renomear/reorganizar | Professor | Mesmo UUID, novos título/posição | Regra acompanha UUID | Sem associação cruzada | Ordem atual disponível | Regra original | PHPUnit escrito |
| Duplicar atividade | Professor | Cópia no mesmo curso | Novo cmid e políticas por UUID | Conforme cópia | Conforme cópia | Conforme cópia | PHPUnit de backup escrito; Moodle real pendente |
| Restaurar outro curso | Administrador | Backup completo | Novo cmid; referências remapeadas | Conforme usuário destino | Conforme usuário destino | Conforme condição restaurada | PHPUnit escrito; Moodle real pendente |
| Excluir atividade | Professor/administrador | Exclusão definitiva | Registros removidos | Não aplicável | Não aplicável | Não aplicável | PHPUnit de observer escrito |
| Limpar cache | Administrador | Purge MUC | Manifesto reextraído; regras preservadas | Sem mudança | Sem mudança | Sem mudança | PHPUnit de cache escrito |

## Comandos de execução

H5P:

```bash
cd src
npm ci
npm test
npm run lint
npm run build
```

Moodle, depois de configurar o banco PHPUnit separado:

```bash
php vendor/bin/phpunit --testsuite local_h5pchapteraccess_testsuite
npx grunt amd --root=local/h5pchapteraccess
php admin/cli/purge_caches.php
```

Testes manuais exigem uma atividade real com esta versão da biblioteca, ao menos um estudante e um professor editor, conclusão/notas/grupos configurados, captura de xAPI e permissões para backup/restore.
