<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

/**
 * High-level TranslatorSage client. Namespaces expose each API family:
 *
 *   $client = new TsageClient('tsa_live_...');
 *   $resp = $client->tts()->synthesize(['text' => 'Hello', 'voice_id' => 'default', 'lang' => 'en']);
 *   $client->translate()->text(['text' => 'hi', 'target_lang' => 'fr']);
 */
class TsageClient
{
    public const DEFAULT_BASE_URL = 'https://developers.translatorsage.com';
    public const VERSION = '0.1.0-alpha';

    /** @var HttpTransport */
    private $http;

    /** @var Consumers */ private $consumers;
    /** @var TTS */ private $tts;
    /** @var STT */ private $stt;
    /** @var Translate */ private $translate;
    /** @var Dubbing */ private $dubbing;
    /** @var Voices */ private $voices;
    /** @var Sfx */ private $sfx;
    /** @var VoiceDesign */ private $voiceDesign;
    /** @var VoiceChanger */ private $voiceChanger;
    /** @var Dialogue */ private $dialogue;
    /** @var AudioIsolation */ private $audioIsolation;
    /** @var Align */ private $align;
    /** @var Diarize */ private $diarize;
    /** @var Agents */ private $agents;

    /**
     * @param string|null $apiKey
     * @param array{base_url?:string,timeout?:int,max_retries?:int,http?:HttpTransport} $options
     */
    public function __construct(?string $apiKey = null, array $options = [])
    {
        if (isset($options['http']) && $options['http'] instanceof HttpTransport) {
            $this->http = $options['http'];
        } else {
            $this->http = new HttpTransport(
                $apiKey,
                $options['base_url'] ?? self::DEFAULT_BASE_URL,
                $options['timeout'] ?? 60,
                $options['max_retries'] ?? 3
            );
        }

        $this->consumers = new Consumers($this->http);
        $this->tts = new TTS($this->http);
        $this->stt = new STT($this->http);
        $this->translate = new Translate($this->http);
        $this->dubbing = new Dubbing($this->http);
        $this->voices = new Voices($this->http);
        $this->sfx = new Sfx($this->http);
        $this->voiceDesign = new VoiceDesign($this->http);
        $this->voiceChanger = new VoiceChanger($this->http);
        $this->dialogue = new Dialogue($this->http);
        $this->audioIsolation = new AudioIsolation($this->http);
        $this->align = new Align($this->http);
        $this->diarize = new Diarize($this->http);
        $this->agents = new Agents($this->http);
    }

    public function consumers(): Consumers { return $this->consumers; }
    public function tts(): TTS { return $this->tts; }
    public function textToSpeech(): TTS { return $this->tts; } // alias
    public function stt(): STT { return $this->stt; }
    public function speechToText(): STT { return $this->stt; }
    public function translate(): Translate { return $this->translate; }
    public function dubbing(): Dubbing { return $this->dubbing; }
    public function voices(): Voices { return $this->voices; }
    public function sfx(): Sfx { return $this->sfx; }
    public function soundEffects(): Sfx { return $this->sfx; }
    public function voiceDesign(): VoiceDesign { return $this->voiceDesign; }
    public function voiceChanger(): VoiceChanger { return $this->voiceChanger; }
    public function dialogue(): Dialogue { return $this->dialogue; }
    public function audioIsolation(): AudioIsolation { return $this->audioIsolation; }
    public function align(): Align { return $this->align; }
    public function diarize(): Diarize { return $this->diarize; }
    public function agents(): Agents { return $this->agents; }

    public function http(): HttpTransport { return $this->http; }
}
