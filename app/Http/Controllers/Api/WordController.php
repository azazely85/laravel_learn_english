<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserWord;
use App\Models\Word;
use App\Models\WordToParse;
use App\MyClasses\BaseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class WordController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getWord(Request $request): JsonResponse
    {
        $vebForms = '';
        $baseService = new BaseService(
            'https://www.oxfordlearnersdictionaries.com/search/english/?q=',
            [], false, 'oxford'
        );
        $result = $baseService->getContents($request->get('name'));

        $wordName = '';
        $wordType = '';
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($result);
        $clearData = $dom->getElementById('entryContent');
        $data = $this->getElementsByClass($clearData, 'h1', 'headword');
        $allowed = "/[^a-z\\040\\.\\-\/]/i";
        foreach ($data as $element) {
            $wordName = preg_replace($allowed, "", trim($element->nodeValue));
        }
        $data = $this->getElementsByClass($clearData, 'span', 'pos');
        foreach ($data as $element) {
            $wordType = $element->nodeValue;
        }
        $checkWord = Word::where('name', $wordName)
            ->orWhere('prsi', $wordName)
            ->orWhere('plural', $wordName)
            ->orWhere('prsh', $wordName)
            ->orWhere('pas', $wordName)
            ->orWhere('pasp', $wordName)
            ->orWhere('ing', $wordName)
            ->orWhere('comparative', $wordName)
            ->orWhere('superlative', $wordName)
            ->first();
        if ($checkWord && $checkWord->type == $wordType) {
            if ($wordName != $request->get('name')) {
                $checkWord->update(['plural' => $request->get('name')]);
            }
            $pieces = explode(" ", $checkWord->name);
            $date = Carbon::now();
            UserWord::where('user_id', 2)
                ->where('word_id', $checkWord->id)->update(['wt' => 0, 'tw' => 0,
                    'audio_test' => count($pieces) > 1 ? 1 : 0, 'start_repeat' => $date]);
            echo $checkWord->translate . '<br />';
            echo $checkWord->description;
            dd(1);
        }
        $data = $this->getElementsByClass($clearData, 'div', 'parallax-container');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'span', 'dictlink-g');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'a', 'topic');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }

        $data = $this->getElementsByClass($clearData, 'div', 'audio_play_button');
        foreach ($data as $element) {
            $name = mb_strtolower(preg_replace('/\s+/', '_', $element->getAttribute('title')));
            if (!Storage::disk('local')->exists($name . '.mp3')) {
                $baseService = new BaseService($element->getAttribute('data-src-mp3'), [], false,
                    'oxford'
                );
                $baseService->saveMp3($name);
            }
            $element->setAttribute('data-src-mp3', 'https://api.vidshup.pp.ua/api/world/voice/' . $name . '_mp3');
            $element->setAttribute('data-src-ogg', 'none');
        }

        $data = $this->getElementsByClass($clearData, 'span', 'topic-g');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'span', 'jumplinks');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'a', 'responsive_display_inline_on_smartphone');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'div', 'pron-link');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'div', 'pron-link');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'span', 'xref_to_full_entry');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'a', 'oup_icons');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'div', 'symbols');
        foreach ($data as $element) {
            $element->parentNode->removeChild($element);
        }
        $count = 1;
        $data = $this->getElementsByClass($clearData, 'a', 'oup_icons');
        foreach ($data as $element) {
            $count++;
            if ($count == 1) {
                continue;
            }
            $element->parentNode->removeChild($element);
        }
        $data = $this->getElementsByClass($clearData, 'span', 'inflected_form');
        $i = 0;
        $comparative = '';
        $superlative = '';
        $prsi = '';
        $prsh = '';
        $pas = '';
        $pas2 = '';
        $pasp = '';
        $pasp2 = '';
        $ing = '';
        if ($wordType == 'adjective') {
            foreach ($data as $element) {
                if ($i == 0) {
                    $comparative = trim($element->nodeValue);
                }
                if ($i == 1) {
                    $superlative = trim($element->nodeValue);
                }
                $i++;
                $vebForms .= trim($element->nodeValue) . ', ';
            }
        }

        $data = $this->getElementsByClass($clearData, 'span', 'box_title');

        foreach ($data as $element) {
            if ($element->nodeValue == 'Oxford Collocations Dictionary') {
                $element->parentNode->parentNode->parentNode->removeChild($element->parentNode->parentNode);
                continue;
            }
            if ($element->nodeValue == 'Extra Examples') {
                $element->parentNode->parentNode->removeChild($element->parentNode);
                continue;
            }

            if ($element->nodeValue == 'Verb Forms') {
                $newData = $element->parentNode->parentNode;
                $newData = $dom->saveXML($newData);
                $dom2 = new \DOMDocument();
                $dom2->loadHTML($newData);
                $clearData2 = $dom2->getElementById($wordName . '_vpgs_1');
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName . '_unbox_1');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName . '2_unbox_1');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName . '3_unbox_1');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName . '_topg_2');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName . '_unbox_2');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName . '_topg_1');
                }

                $vebFormsData = $this->getElementsByClass($clearData2, 'td', 'verb_form');

                $replacement = array(
                    "present simple I / you / we / they ",
                    "he / she / it ",
                    "past simple ",
                    "past participle ",
                    "-ing form "
                );
                $i = 0;

                if (count($vebFormsData) == 5) {
                    foreach ($vebFormsData as $vebForm) {
                        if ($i == 0) {
                            $prsi = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 1) {
                            $prsh = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 2) {
                            $pas = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 3) {
                            $pasp = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 4) {
                            $ing = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        $i++;
                        $vebForms .= trim(str_replace($replacement, "", $vebForm->nodeValue)) . ', ';
                    }
                } else {
                    foreach ($vebFormsData as $vebForm) {
                        if ($i == 0) {
                            $prsi = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 1) {
                            $prsh = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 2) {
                            $pas = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 3) {
                            $pasp = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 4) {
                            $pas2 = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 5) {
                            $pasp2 = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        if ($i == 6) {
                            $ing = trim(str_replace($replacement, "", $vebForm->nodeValue));
                        }
                        $i++;
                        $vebForms .= trim(str_replace($replacement, "", $vebForm->nodeValue)) . ', ';
                    }
                }
            }
        }

        $vebForms = mb_substr($vebForms, 0, mb_strlen($vebForms) - 2);
        echo '///////////////////////////////////////////////////////////////////////////////////////////////';
        echo $vebForms;
        echo '///////////////////////////////////////////////////////////////////////////////////////////////';
        $data = $this->getElementsByClass($clearData, 'a', 'ref');
        foreach ($data as $element) {
            $check = WordToParse::where('name', $element->nodeValue)->first();
            $checkWord = Word::where('prsi', $element->nodeValue)
                ->orWhere('prsh', $element->nodeValue)
                ->orWhere('pas', $element->nodeValue)
                ->orWhere('pasp', $element->nodeValue)
                ->orWhere('ing', $element->nodeValue)
                ->orWhere('comparative', $element->nodeValue)
                ->orWhere('superlative', $element->nodeValue)
                ->orWhere('plural', $wordName)
                ->first();
            if (!$check && !$checkWord) {
                WordToParse::create(['url' => $element->getAttribute('href'), 'name' => $element->nodeValue]);
            }
            $element->setAttribute(
                'href',
                '/word/' . mb_strtolower(
                    preg_replace(
                        '/\s+/',
                        '_',
                        $element->nodeValue
                    )
                )
            );
        }
        $data = $this->getElementsByClass($clearData, 'span', 'xr-g');
        foreach ($data as $element) {
            $element->removeAttribute('href');
        }
        $data = $this->getElementsByClass($clearData, 'span', 'def');
        foreach ($data as $element) {
            $element->removeAttribute('htag');
            $element->removeAttribute('hclass');
        }
        $data = $this->getElementsByClass($clearData, 'h2', 'shcut');
        foreach ($data as $element) {
            $element->removeAttribute('htag');
            $element->removeAttribute('hclass');
            $element->removeAttribute('id');
        }
        $data = $this->getElementsByClass($clearData, 'span', 'grammar');
        foreach ($data as $element) {
            $element->removeAttribute('htag');
            $element->removeAttribute('hclass');
        }
        $data = $this->getElementsByClass($clearData, 'span', 'shcut-g');
        foreach ($data as $element) {
            $element->removeAttribute('id');
        }

        $clearDataRing = $dom->getElementById('ring-links-box');
        if ($clearDataRing) {
            $clearDataRing->parentNode->removeChild($clearDataRing);
        }
        $data = $dom->saveXML($clearData);
        $translate = '';

        if (!$translate) {
            $baseService = new BaseService('https://e2u.org.ua/s?w=', [], false, 'dict');
            $result = $baseService->getContents($wordName . '&dicts=all');
            libxml_use_internal_errors(true);
            $domUa = new \DOMDocument();
            $domUa->loadHTML($result);
            $clearDataUa = $domUa->getElementById('table_17');
            if (!$clearDataUa) {
                $clearDataUa = $domUa->getElementById('table_2');
            }
            if (!$clearDataUa) {
                $clearDataUa = $domUa->getElementById('table_1');
            }
            if (!$clearDataUa) {
                $clearDataUa = $domUa->getElementById('table_18');
            }

            if ($clearDataUa) {
                $dataUa = $this->getElementsByClass($clearDataUa, 'td', 'result_row_main');
                foreach ($dataUa as $element) {
                    $translate .= $element->nodeValue . ', ';
                }
                $translate = stristr($translate, '1');
                $pos = strpos($translate, '2');
                $translate = substr($translate, 0, $pos);
                $translate = mb_substr($translate, 2, mb_strlen($translate));
            }
        }

        if (!$translate) {
            $baseService = new BaseService(
                'https://dict.com/%D0%B0%D0%BD%D0%B3%D0%BB%D1%96%D0%B8%D1%81%D1%8C%D0%BA%D0%BE-%D1%83%D0%BA%D1%80%D0%B0%D1%96%D0%BD%D1%81%D1%8C%D0%BA%D0%B8%D0%B8/',
                [],
                false,
                'dict'
            );
            $result = $baseService->getContents($wordName);
            if ($result) {
                libxml_use_internal_errors(true);
                $domUa = new \DOMDocument();
                $domUa->loadHTML($result);
                $clearDataUa = $domUa->getElementById('entry-wrapper');
                $dataUa = $this->getElementsByClass($clearDataUa, 'span', 'lex_ful_tran');
                foreach ($dataUa as $element) {
                    $translate .= $element->nodeValue . ', ';
                }
                if (!$translate) {
                    $dataUa = $this->getElementsByClass($clearDataUa, 'span', 'lex_ful_coll2t');
                    foreach ($dataUa as $element) {
                        $translate .= $element->nodeValue . ', ';
                    }
                }
            }
        }
        $translate = mb_substr($translate, 0, mb_strlen($translate) - 2);
        if (mb_strlen($translate) > 500) {
            $translate = mb_substr($translate, 0, mb_strlen($translate) - (mb_strlen($translate) - 499));
        }
        echo $translate;
        echo $data;
        $plural = '';
        if ($wordName != trim($request->get('name'))) {
            $plural = $request->get('name');
        }
        $user = User::find(2);
        if ($wordName) {
            $word = Word::create([
                'name' => $wordName,
                'type' => $wordType,
                'description' => $data,
                'veb_forms' => $vebForms,
                'translate' => $translate,
                'comparative' => $comparative,
                'superlative' => $superlative,
                'prsi' => $prsi,
                'prsh' => $prsh,
                'pas' => $pas,
                'pas2' => $pas2,
                'pasp' => $pasp,
                'pasp2' => $pasp2,
                'ing' => $ing,
                'plural' => $plural
            ]);

            $user->words()->attach($word->id);
        }
        $pieces = explode(" ", $wordName);
        if (count($pieces) > 1) {
            UserWord::where('word_id', $word->id)->update(['audio_test' => 1]);
        }

        dd(1);
        $user = auth()->user();
        $user->words()->attach($word->id);
        return response()->json(['status' => 'success', 'data' => $user], 200);
    }

    public function getUserWords(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $words = User::where('users.id', $authUser)
            ->leftJoin('user_word', 'user_word.user_id', '=', 'users.id')
            ->leftJoin('word', 'user_word.word_id', '=', 'word.id')
            ->select('word.name', 'user_word.*', 'word.id', 'word.translate')
            ->get();

        return response()->json(['status' => 'success', 'data' => $words], 200);
    }

    public function voice(Request $request, $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filePath = storage_path() . '/app/' . $id;
        $filePath = str_replace('_mp3', '.mp3', $filePath);
        return response()->file($filePath);
    }

    protected function getElementsByClass(&$parentNode, $tagName, $className): array
    {
        $nodes = array();
        if ($parentNode) {
            $childNodeList = $parentNode->getElementsByTagName($tagName);
            for ($i = 0; $i < $childNodeList->length; $i++) {
                $temp = $childNodeList->item($i);
                if (stripos($temp->getAttribute('class'), $className) !== false) {
                    $nodes[] = $temp;
                }
            }
        }


        return $nodes;
    }
}
