<?php

declare(strict_types=1);

namespace Tracker\Services;

use Tracker\Question;

class NELQAnalyser implements ServicesInterface {
	/**
	 * Chat API to talk in the chat
	 *
	 * @var ChatAPI
	 */
	private $chatAPI = null;

	/**
	 * Chat rooms to report to
	 *
	 * @var array
	 */
	private $chatrooms = [];

	/**
	 * What to say when a post is reported
	 *
	 * @var string
	 */
	private $reportText = '';

	const LANGS = [
		'es' => 'Spanish',
		'pt' => 'Portuguese',
		'ru' => 'Russian',
		'zh' => 'Chinese',
		'ta' => 'Tamil',
		'ar' => 'Arabic',
		'bn' => 'Bengali',
		'Deva' => 'Devanagari',
		'el' => 'Greek',
		'gu' => 'Gujarati',
		'ko' => 'Korean',
		'id' => 'Indonesian',
		'vi' => 'Vietnamese',
		'fr' => 'French',
		'it' => 'Italian',
		'kn' => 'Kannada',
		'Kana' => 'Katakana',
		'ml' => 'Malayalam',
		'te' => 'Telugu',
		'th' => 'Thai',
		'tr' => 'Turkish',
	];

	public function __construct(\ChatAPI $chatAPI, \DotEnv $dotEnv) {
		$this->chatAPI = $chatAPI;

		$this->reportText = $dotEnv->get('NELQA_report_text');
		$this->chatrooms = $dotEnv->get('chatrooms')['NELQA'];

		// Say hello
		foreach ($this->chatrooms as $roomId) {
			$this->chatAPI->sendMessage($roomId, 'NELQA started on '.gethostname());
		}
	}

	/**
	 * Execute question scan
	 *
	 * @param Question $post
	 * @return void
	 */
	public function execute(Question $post): void {
		// for all non-closed questions, execute non-English language question analysis
		if (!$post->closed_date) {
			$reason = '';
			if ($lang = $this->checkLanguage($post)) {
				$reason = self::LANGS[$lang]." content detected";
			} elseif ($this->noLatinLetters($post)) {
				$reason = 'Probably non-English question or VLQ';
			}
			// report in all relevant rooms
			if ($reason) {
				foreach ($this->chatrooms as $roomId) {
					$this->chatAPI->sendMessage($roomId, sprintf($this->reportText, $post->link, $reason));
				}
			}
		}
	}

	private function checkLanguage(Question $post) {
		$m = [];

		// Watch for most common words/phrases appearing in Stack Overflow's questions
		$langKeywords = [
			'es' => '\bcodigos?\b|\bpero\b|resultado|\bc(?:o|ó)mo\b|\bHola\b|tengo|\bcomo\b|ayud(?:a|r?eme)|est(?:oy|á|e)|Buenos|SALUDO|vamos|Gracias|nuevo|\bAQUI\b|por adelantado|anticipación|¿|¡|\bpued(?:o|a)\b|aplicación|solución|función|espero|alguien|\buna\b|siguiente|Alguna|sugerencia|selección|Tenemos|llamar|palabra|Cómo|codifcar|podria|mucho|Queremos|tiempo|haciendo|\bparar\b|usuario|número|\bdatos\b|de dato|el tipo|código|\bmuy\b|\bestoy?\b|encontré|\bobjeto\b|había|\bentrar\b|\bque\b|\bpude\b|ustedes|algunos?|puedo|puede|saber|necesito|nombre',
			'pt' => 'boa tarde|ajude|\btodas\b|\bvoc(?:ê|e)\b|\best(?:á|a)\b|estão|\bcomo\b|vamos|\bestou\b|minha|quando|então|tenho|\bquero\b|\bquem\b|porque|obrigad(?:a|o)|\bJá\b|\bTento\b|\berro\b|(?:de )?dados|\bfunciona\b|Olá|resultou|RESULTADO|Alguma|linha|antecipadamente|dúvida|minha|aplicação|versão|\bpagina\b|\bdois\b|Sou novo|\bnão\b|\bpassar\b|têm|\bparar\b|código|\bfazer\b|\btoda\b|senha|conseguir|será|Alguém',
			'fr' => 'Bonjour|j\'ai|Merci|problème|Aidez(?:-| )moi|s\'il vous plaît|\baider\b|\bje\b|Erreur|\bavec\b|\bmoi\b|\bsais\b|\bdeux\b|J\'aimerai|\bune\b|j\'essaye|\bvous\b|\bavons\b|création|\bvotre\b|voudrais|\bavoir\b|besoin|données|toutes|tableur|connais|veux|nombre|utilisateur|Créé|ajouter|était|C\'est',
			'id' => 'Tolong|Selamat|masalah|bagaimana|\bkapan\b|\bsaya\b|\bsudah\b|Terima kasih|\bjual\b|\bobat\b', //indonesian
			'vi' => 'cảm|ơn|Tôi|có|chào|với|giúp|đó|lệnh|lỗi|này|mình|làm|nào|ngày|có|thể|nhỏ|tốt', // vietnamese
			'it' => 'per favore|\baiuto\b|aiutami|Buongiorno|buona serata|io ho|domanda|\bpagina\b', // italian
			'th' => '\p{Thai}{3,}',
			'tr' => 'içine|olucak|sayfası|değişken|oluşturudum|kalmıyor|gün|içinde|siliniyor|oluşturup|çıkıyor|istediğim|Sahibim|ihtiyacım|ihtiyaç|Teşekkür(?:ler)?|Merhaba|Günaydın|Nasıl|ne zaman|\bhata\b|calismiyor|kodlari?|verip|lütfen|Yardım|arkadaşlar|oluştu|herşey|calışmıyor|olmadı|yaptım', // turkish
		];

		foreach ($langKeywords as $lang => $keywords) {
			if (preg_match_all('#'.$keywords.'#iu', $post->bodyStrippedWithTitle, $matches, PREG_SET_ORDER)) {
				$vals = array_unique(array_column($matches, 0));
				if (count($vals) >= 3) {
					$m[$lang] = $vals;
				}
			}
		}

		// Watch for non-latin scripts
		$langRegexes = [
			'ru' => '\p{Cyrillic}{3,}', // has spaces
			'ar' => '\p{Arabic}{3,}', // has spaces
			'bn' => '\p{Bengali}{3,}', // has spaces
			'zh' => '\p{Han}',
			'ta' => '\p{Tamil}{3,}', // has spaces
			'Deva' => '\p{Devanagari}{2,}', // has spaces
			'el' => '\p{Greek}{3,}', // has spaces
			'gu' => '\p{Gujarati}{3,}', // has spaces
			'ko' => '\p{Hangul}{2,}', // has spaces
			'kn' => '\p{Kannada}{3,}', // has spaces
			'Kana' => '[\p{Han}\p{Katakana}\p{Hiragana}]',
			'ml' => '\p{Malayalam}',
			'te' => '\p{Telugu}',
		];

		foreach ($langRegexes as $lang => $keywords) {
			if (preg_match_all('#'.$keywords.'#iu', $post->bodyStrippedWithTitle, $matches, PREG_SET_ORDER)) {
				$vals = array_unique(array_column($matches, 0));
				if (count($vals) >= 8) {
					$m[$lang] = $vals;
				}
			}
		}

		if ($m) {
			array_multisort(array_map('count', $m), SORT_DESC, $m);
			return array_keys($m)[0];
		}
	}

	private function noLatinLetters($post) {
		if (strlen($post->bodyStrippedWithTitle) < 10) {
			return false;
		}

		preg_match_all(
			'#[a-z]#iu',
			$post->bodyStrippedWithTitle,
			$m1,
		);

		// If there are less than 7 latin characters in the non-formatted text then it is likely the post is not in English or VLQ
		return count(array_unique($m1[0])) <= 6;
	}
}
