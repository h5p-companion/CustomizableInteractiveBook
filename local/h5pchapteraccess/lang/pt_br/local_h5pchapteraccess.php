<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese language strings for local_h5pchapteraccess.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['accessmode'] = 'Modo de acesso';
$string['accessmode_help'] = 'Sempre disponível exibe o capítulo. Sempre bloqueado impede seu conteúdo. Condicional usa '
    . 'as restrições de disponibilidade do Moodle configuradas para o capítulo.';
$string['activechaptercount'] = 'Capítulos ativos';
$string['activechapters'] = 'Capítulos ativos';
$string['activityname'] = 'Atividade';
$string['activitysettings'] = 'Configurações da atividade';
$string['activitysummary'] = 'Atividade e manifesto';
$string['availabilityinvalid'] = 'Este capítulo está indisponível porque não foi possível avaliar suas condições de acesso.';
$string['availabilitydisabled'] = 'A disponibilidade condicional está desabilitada neste site Moodle.';
$string['availabilitypreservationnotice'] = 'Estas condições são preservadas em todos os modos, mas são avaliadas somente '
    . 'quando o modo é Condicional.';
$string['bridgeasset'] = 'Ponte de comunicação do Moodle';
$string['bridgeassetmissing'] = 'O arquivo AMD gerado não foi encontrado. Reinstale o plugin completo e limpe os caches.';
$string['bridgeassetpresent'] = 'Arquivo AMD gerado encontrado';
$string['chaptercounts'] = 'Modos atuais dos capítulos';
$string['chapteravailabilityname'] = 'Capítulo H5P: {$a}';
$string['chapterconfigurationsaved'] = 'O acesso e as condições de disponibilidade do capítulo foram salvos.';
$string['chapterpositionheading'] = 'Capítulo {$a}';
$string['chaptersettings'] = 'Configurações do capítulo';
$string['chaptertitle'] = 'Título';
$string['chapteruuid'] = 'ID do capítulo';
$string['checkattention'] = 'Atenção';
$string['checkerror'] = 'Correção necessária';
$string['checkok'] = 'OK';
$string['configurationsaved'] = 'A configuração de acesso aos capítulos foi salva.';
$string['conditionalwithoutconditions'] = 'O modo condicional não possui condições configuradas; o capítulo permanece disponível.';
$string['conditionaleditafter_save'] = 'Selecione Condicional e salve para habilitar o botão Editar restrições deste capítulo.';
$string['conditionalsettings'] = 'Condições do Moodle';
$string['conditionconfigurationhint'] = 'Na próxima página você pode selecionar Condicional e configurar as condições '
    . 'no mesmo salvamento. Condições armazenadas são ignoradas nos outros modos.';
$string['configurationattentiontitle'] = 'A configuração precisa de atenção.';
$string['configurationguide'] = 'Como funciona o acesso aos capítulos';
$string['configurationguideintro'] = 'Habilite a integração, escolha um modo para cada capítulo com ID estável e salve. '
    . 'Depois, abra a atividade com o modo de edição do Moodle desligado para conferir a visão do estudante.';
$string['configurationneedsattention'] = 'Revise as verificações abaixo. Quando a comunicação está incompleta, o fallback '
    . 'de segurança do H5P mantém todos os capítulos disponíveis.';
$string['configurationready'] = 'Os arquivos necessários estão presentes, a integração está habilitada e pelo menos um '
    . 'capítulo possui regra de acesso.';
$string['configurationreadytitle'] = 'Pronto para aplicar o acesso aos capítulos.';
$string['configurationstatus'] = 'Situação da instalação e da configuração';
$string['configureconditions'] = 'Configurar acesso condicional';
$string['configuredrules'] = 'Capítulos bloqueados ou condicionais';
$string['configuredrulesvalue'] = '{$a->restricted} de {$a->total} capítulos ativos';
$string['conditionssummary'] = 'Restrições configuradas';
$string['contenthash'] = 'Hash do conteúdo';
$string['contentid'] = 'ID do conteúdo H5P';
$string['coursemoduleid'] = 'ID do módulo do curso';
$string['currentmode'] = 'Modo salvo';
$string['defaultmessage'] = 'Mensagem padrão de bloqueio';
$string['defaultmessage_help'] = 'Mensagem em texto simples exibida quando um capítulo bloqueado não possui mensagem específica.';
$string['defaultlockedmessage'] = 'Este capítulo está indisponível no momento.';
$string['deploymentnotice'] = 'Copiar a pasta do plugin para outro Moodle não copia as regras desta atividade no banco '
    . 'e não instala a biblioteca H5P modificada. Em cada Moodle, instale H5P.CustomizableInteractiveBook 1.0.32 ou '
    . 'superior, execute a atualização do plugin, limpe os caches, abra esta página, habilite a integração e configure '
    . 'os capítulos.';
$string['editchapterheading'] = 'Capítulo {$a->position}: {$a->title}';
$string['editrestrictions'] = 'Editar restrições';
$string['editrestrictionstitle'] = 'Editar restrições do capítulo';
$string['error:accessdenied'] = 'Você não tem acesso à atividade H5P {$a}.';
$string['error:cmnotfound'] = 'O módulo de curso {$a} não existe.';
$string['error:configurationmissing'] = 'A configuração de capítulos da atividade ainda não foi sincronizada.';
$string['error:contentmismatch'] = 'O conteúdo H5P solicitado não pertence a esta atividade.';
$string['error:duplicatechapterid'] = 'O livro H5P contém o ID de capítulo duplicado "{$a}".';
$string['error:h5pnotfound'] = 'Nenhum conteúdo core H5P processado foi encontrado para a atividade {$a}.';
$string['error:incompatiblelibrary'] = 'A biblioteca H5P principal "{$a}" não é compatível.';
$string['error:instancenotfound'] = 'A instância h5pactivity do módulo de curso {$a} não existe.';
$string['error:invalidchapters'] = 'O livro H5P possui uma configuração de capítulos inválida.';
$string['error:invalidjson'] = 'Os parâmetros do conteúdo H5P não são um JSON válido.';
$string['error:chapternotcurrent'] = 'O capítulo solicitado não está presente no manifesto H5P atual.';
$string['error:chapternotfound'] = 'O capítulo solicitado não pertence à configuração desta atividade.';
$string['error:inactivechapter'] = 'Capítulos inativos não podem ser editados.';
$string['error:unstablechapter'] = 'Um capítulo sem subContentId estável não pode ter restrições persistentes editadas.';
$string['error:jsonunavailable'] = 'Não foi possível ler content/content.json do pacote H5P.';
$string['error:packagenotfound'] = 'Nenhum pacote H5P foi encontrado para a atividade {$a}.';
$string['error:wrongmodule'] = 'O módulo de curso selecionado é "{$a}", e não h5pactivity.';
$string['h5pchapteraccess:manage'] = 'Gerenciar o acesso aos capítulos H5P';
$string['h5pchapteraccess:viewlocked'] = 'Visualizar capítulos H5P bloqueados durante a edição';
$string['inactivechapters'] = 'Capítulos inativos';
$string['inactivechapterscaption'] = 'Capítulos mantidos para diagnóstico e reativação futura';
$string['inactivechaptersdescription'] = 'Estes registros não aparecem no manifesto atual. Suas configurações são '
    . 'preservadas e não podem ser editadas aqui.';
$string['integrationenabled'] = 'Habilitar integração de acesso aos capítulos';
$string['integrationenabled_help'] = 'Quando habilitada, a política configurada poderá ser aplicada à visualização do '
    . 'estudante. Desabilitar preserva todas as configurações dos capítulos.';
$string['integrationstatus'] = 'Integração';
$string['invalidaccessmode'] = 'Selecione Sempre disponível, Sempre bloqueado ou Condicional.';
$string['invalidavailabilityconditions'] = 'As condições de disponibilidade não são válidas.';
$string['manifesthash'] = 'Hash do manifesto';
$string['modelocked'] = 'Sempre bloqueado';
$string['modelockeddescription'] = 'O conteúdo real do capítulo não é inicializado e uma explicação em texto simples é exibida.';
$string['modeconditional'] = 'Condicional';
$string['modeconditionaldescription'] = 'O Moodle avalia as condições de data, grupo, nota, conclusão ou outras restrições '
    . 'configuradas para cada usuário.';
$string['modeopen'] = 'Sempre disponível';
$string['modeopendescription'] = 'O capítulo fica disponível para os usuários que podem acessar a atividade.';
$string['modeunavailable'] = 'Não editável nesta versão';
$string['navigationtitle'] = 'Acesso aos capítulos H5P';
$string['noactivechapters'] = 'Nenhum capítulo ativo foi encontrado no manifesto atual.';
$string['norestrictionsconfigured'] = 'Sem restrições configuradas.';
$string['pagetitle'] = 'Acesso aos capítulos H5P';
$string['pluginname'] = 'Acesso aos capítulos H5P';
$string['position'] = 'Posição';
$string['openactivity'] = 'Abrir a atividade H5P';
$string['privacy:metadata'] = 'O plugin armazena regras de acesso pertencentes às atividades e aos capítulos, não aos '
    . 'usuários individuais.';
$string['specificmessage'] = 'Mensagem de bloqueio específica';
$string['specificmessage_help'] = 'Mensagem opcional em texto simples para este capítulo. Deixe vazia para usar a '
    . 'mensagem padrão da atividade.';
$string['showrestriction'] = 'Mostrar ao estudante a explicação da restrição';
$string['showrestriction_help'] = 'Quando habilitada, a informação da condição Moodle pode ser usada depois da mensagem '
    . 'específica e antes da mensagem padrão da atividade. A informação é convertida em texto simples antes de ser '
    . 'enviada ao H5P.';
$string['stableid'] = 'ID estável';
$string['stableidno'] = 'Não';
$string['stableidyes'] = 'Sim';
$string['statusdisabled'] = 'Desabilitada';
$string['statusenabled'] = 'Habilitada';
$string['studentviewnotice'] = 'Estas configurações controlam a disponibilidade dos capítulos na visualização do '
    . 'estudante. Um capítulo bloqueado continua empacotado no arquivo H5P, mas sua biblioteca filha não é inicializada '
    . 'pela biblioteca de Livro Interativo compatível.';
$string['technicaldetails'] = 'Detalhes técnicos';
$string['libraryruntime'] = 'Biblioteca H5P instalada';
$string['libraryversionunknown'] = 'Não foi possível determinar a versão';
$string['libraryversionvalue'] = '{$a->installed} (mínima com o contrato do hospedeiro: {$a->minimum})';
$string['restrictioneditorintro'] = 'A árvore abaixo é o editor padrão da Availability API do Moodle. Os tipos de '
    . 'condição disponíveis dependem dos plugins habilitados neste site e curso.';
$string['synchronizeagain'] = 'Sincronizar manifesto novamente';
$string['synchronizationsuccess'] = 'O manifesto de capítulos foi sincronizado.';
$string['unstableidwarning'] = 'Este capítulo não possui subContentId permanente. Seu ID alternativo depende da posição '
    . 'atual; por isso, a configuração persistente de acesso está desabilitada.';
$string['unstablecountnotice'] = '{$a} capítulo(s) não possuem ID estável e não podem manter uma regra persistente.';
$string['unsupportedmodewarning'] = 'Este capítulo usa um modo reservado para uma versão futura e não pode ser editado '
    . 'nesta página.';
$string['accessactive'] = 'O acesso aos capítulos está habilitado.';
$string['accessactivemessage'] = 'As regras salvas são aplicadas quando a atividade é aberta.';
$string['accesspaused'] = 'O acesso aos capítulos está pausado.';
$string['accesspausedmessage'] = 'As regras continuam salvas, mas todos os capítulos ficam disponíveis.';
$string['chaptereditorintro'] = 'Escolha como este capítulo deve funcionar. As opções condicionais aparecem imediatamente.';
$string['chapterlistintro'] = 'Escolha um capítulo para configurar o acesso. Títulos e posições vêm automaticamente do livro.';
$string['communicationproblem'] = 'As regras podem não chegar ao Livro Interativo.';
$string['conditionalinlineintro'] = 'Adicione uma ou mais condições do Moodle. É possível combinar datas, grupos, notas, '
    . 'conclusão e outras restrições habilitadas.';
$string['configurechapter'] = 'Configurar';
$string['countconditional'] = 'Condicionais';
$string['countlocked'] = 'Bloqueados';
$string['countopen'] = 'Disponíveis';
$string['editingchapter'] = 'Editando';
$string['generalsettings'] = 'Configurações gerais';
$string['generalsettingsintro'] = 'Habilite ou pause a integração e defina a mensagem usada quando um capítulo estiver bloqueado.';
$string['integrationenabledshort'] = 'Aplicar regras de acesso aos capítulos';
$string['libraryincompatibleaction'] = 'Instale H5P.CustomizableInteractiveBook 1.0.32 ou superior e limpe os caches do Moodle.';
$string['norulesconfigured'] = 'Todos os capítulos estão disponíveis. Configure um capítulo abaixo para adicionar um bloqueio ou condição.';
$string['previewactivity'] = 'Visualizar atividade';
$string['savechapteraccess'] = 'Salvar capítulo';
$string['savegeneralsettings'] = 'Salvar configurações gerais';
$string['showrestrictionshort'] = 'Explicar ao estudante qual condição ainda não foi atendida';
$string['unstableidteachershort'] = 'Este capítulo não pode manter uma regra confiável porque não possui identificador permanente.';
$string['usesdefaultmessage'] = 'Deixe vazio para usar a mensagem padrão definida acima.';
