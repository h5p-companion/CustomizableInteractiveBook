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
$string['activechaptercount'] = 'Capítulos ativos';
$string['activechapters'] = 'Capítulos ativos';
$string['activityname'] = 'Atividade';
$string['activitysettings'] = 'Configurações da atividade';
$string['activitysummary'] = 'Atividade e manifesto';
$string['availabilityinvalid'] = 'Este capítulo está indisponível porque não foi possível avaliar suas condições de acesso.';
$string['availabilitydisabled'] = 'A disponibilidade condicional está desabilitada neste site Moodle.';
$string['availabilitypreservationnotice'] = 'Estas condições são preservadas em todos os modos, mas são avaliadas somente quando o modo é Condicional.';
$string['chapteravailabilityname'] = 'Capítulo H5P: {$a}';
$string['chapterconfigurationsaved'] = 'O acesso e as condições de disponibilidade do capítulo foram salvos.';
$string['chapterpositionheading'] = 'Capítulo {$a}';
$string['chaptersettings'] = 'Configurações do capítulo';
$string['chaptertitle'] = 'Título';
$string['chapteruuid'] = 'ID do capítulo';
$string['configurationsaved'] = 'A configuração de acesso aos capítulos foi salva.';
$string['conditionalwithoutconditions'] = 'O modo condicional não possui condições configuradas; o capítulo permanece disponível.';
$string['conditionaleditafter_save'] = 'Selecione Condicional e salve para habilitar o botão Editar restrições deste capítulo.';
$string['conditionssummary'] = 'Restrições configuradas';
$string['contenthash'] = 'Hash do conteúdo';
$string['contentid'] = 'ID do conteúdo H5P';
$string['coursemoduleid'] = 'ID do módulo do curso';
$string['defaultmessage'] = 'Mensagem padrão de bloqueio';
$string['defaultmessage_help'] = 'Mensagem em texto simples exibida quando um capítulo bloqueado não possui mensagem específica.';
$string['defaultlockedmessage'] = 'Este capítulo está indisponível no momento.';
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
$string['h5pchapteraccess:viewlocked'] = 'Visualizar capítulos H5P bloqueados';
$string['inactivechapters'] = 'Capítulos inativos';
$string['inactivechapterscaption'] = 'Capítulos mantidos para diagnóstico e reativação futura';
$string['inactivechaptersdescription'] = 'Estes registros não aparecem no manifesto atual. Suas configurações são preservadas e não podem ser editadas aqui.';
$string['integrationenabled'] = 'Habilitar integração de acesso aos capítulos';
$string['integrationenabled_help'] = 'Quando habilitada, a política configurada poderá ser aplicada à visualização do estudante. Desabilitar preserva todas as configurações dos capítulos.';
$string['integrationstatus'] = 'Integração';
$string['invalidaccessmode'] = 'Selecione Sempre disponível, Sempre bloqueado ou Condicional.';
$string['invalidavailabilityconditions'] = 'As condições de disponibilidade não são válidas.';
$string['manifesthash'] = 'Hash do manifesto';
$string['modelocked'] = 'Bloqueado';
$string['modeconditional'] = 'Condicional';
$string['modeopen'] = 'Disponível';
$string['modeunavailable'] = 'Não editável nesta versão';
$string['navigationtitle'] = 'Acesso aos capítulos H5P';
$string['noactivechapters'] = 'Nenhum capítulo ativo foi encontrado no manifesto atual.';
$string['norestrictionsconfigured'] = 'Sem restrições configuradas.';
$string['pagetitle'] = 'Acesso aos capítulos H5P';
$string['pluginname'] = 'Acesso aos capítulos H5P';
$string['position'] = 'Posição';
$string['privacy:metadata'] = 'O plugin armazena regras de acesso pertencentes às atividades e aos capítulos, não aos usuários individuais.';
$string['specificmessage'] = 'Mensagem de bloqueio específica';
$string['specificmessage_help'] = 'Mensagem opcional em texto simples para este capítulo. Deixe vazia para usar a mensagem padrão da atividade.';
$string['showrestriction'] = 'Mostrar ao estudante a explicação da restrição';
$string['showrestriction_help'] = 'Quando habilitada, a informação da condição Moodle pode ser usada depois da mensagem específica e antes da mensagem padrão da atividade. A informação é convertida em texto simples antes de ser enviada ao H5P.';
$string['stableid'] = 'ID estável';
$string['stableidno'] = 'Não';
$string['stableidyes'] = 'Sim';
$string['statusdisabled'] = 'Desabilitada';
$string['statusenabled'] = 'Habilitada';
$string['studentviewnotice'] = 'Estas configurações controlam a disponibilidade dos capítulos na visualização do estudante. Um capítulo bloqueado continua empacotado no arquivo H5P, mas sua biblioteca filha não é inicializada pela biblioteca de Livro Interativo compatível.';
$string['restrictioneditorintro'] = 'A árvore abaixo é o editor padrão da Availability API do Moodle. Os tipos de condição disponíveis dependem dos plugins habilitados neste site e curso.';
$string['synchronizeagain'] = 'Sincronizar manifesto novamente';
$string['synchronizationsuccess'] = 'O manifesto de capítulos foi sincronizado.';
$string['unstableidwarning'] = 'Este capítulo não possui subContentId permanente. Seu ID alternativo depende da posição atual; por isso, a configuração persistente de acesso está desabilitada.';
$string['unsupportedmodewarning'] = 'Este capítulo usa um modo reservado para uma versão futura e não pode ser editado nesta página.';
