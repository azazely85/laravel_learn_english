<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Word;
use App\Models\WordToParse;
use App\MyClasses\BaseService;
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
    public function getWord(Request $request)
    {
//        dd($request->get('name'));
        $checkWord = Word::where('name', $request->get('name'))
            ->orWhere('prsi', $request->get('name'))
            ->orWhere('prsh', $request->get('name'))
            ->orWhere('plural', $request->get('name'))
            ->orWhere('pas', $request->get('name'))
            ->orWhere('pasp', $request->get('name'))
            ->orWhere('ing', $request->get('name'))
            ->orWhere('comparative', $request->get('name'))
            ->orWhere('superlative', $request->get('name'))
            ->first();
        if ($checkWord) {
            dd($checkWord->translate);
        }
        $veb_forms = '';
        $baseService = new BaseService('https://www.oxfordlearnersdictionaries.com/search/english/?q=', [], false,
            'oxford');
        $result = $baseService->getContents( $request->get('name'));

        $wordName = '';
        $wordType = '';
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($result);
        $clearData = $dom->getElementById('entryContent');
        $data = $this->getElementsByClass($clearData, 'h1', 'headword');
        $allowed = "/[^a-z\\040\\.\\-\/]/i";
        foreach($data as $paralax) {
            $wordName = preg_replace($allowed,"",trim($paralax->nodeValue));
        }
        $data = $this->getElementsByClass($clearData, 'span', 'pos');
        foreach($data as $paralax) {
            $wordType = $paralax->nodeValue;
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
            dd($checkWord);
        }
        $data = $this->getElementsByClass($clearData, 'div', 'parallax-container');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'span', 'dictlink-g');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'a', 'topic');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }

        $data = $this->getElementsByClass($clearData, 'div', 'audio_play_button');
        foreach($data as $paralax) {
            $name = mb_strtolower(preg_replace('/\s+/', '_', $paralax->getAttribute('title')));
            if (!Storage::disk('local')->exists($name.'.mp3')) {
                $baseService = new BaseService($paralax->getAttribute('data-src-mp3'), [], false,
                    'oxford');
                $baseService->saveMp3($name);
            }
            $paralax->setAttribute('data-src-mp3', 'http://laravel.local/api/world/voice/'.$name.'_mp3');
            $paralax->setAttribute('data-src-ogg', 'none');
        }

        $data = $this->getElementsByClass($clearData, 'span', 'topic-g');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'span', 'jumplinks');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'a', 'responsive_display_inline_on_smartphone');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'div', 'pron-link');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'div', 'pron-link');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'span', 'xref_to_full_entry');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'a', 'oup_icons');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'div', 'symbols');
        foreach($data as $paralax) {
            $paralax->parentNode->removeChild($paralax);
        }
        $count = 1;
        $data = $this->getElementsByClass($clearData, 'a', 'oup_icons');
        foreach($data as $paralax) {
            $count++;
            if ($count == 1) {
                continue;
            }
            $paralax->parentNode->removeChild($paralax);
        }
        $data = $this->getElementsByClass($clearData, 'span', 'inflected_form');
        $i=0;
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
            foreach($data as $paralax) {
                if ($i == 0) {
                    $comparative = trim($paralax->nodeValue);
                }
                if ($i == 1) {
                    $superlative = trim($paralax->nodeValue);
                }
                $i++;
                $veb_forms .= trim($paralax->nodeValue) . ', ';
            }
        }

        $data = $this->getElementsByClass($clearData, 'span', 'box_title');

        foreach($data as $paralax) {
            if ($paralax->nodeValue == 'Oxford Collocations Dictionary') {
                $paralax->parentNode->parentNode->parentNode->removeChild($paralax->parentNode->parentNode);
                continue;
            }
            if ($paralax->nodeValue == 'Extra Examples') {
                $paralax->parentNode->parentNode->removeChild($paralax->parentNode);
                continue;
            }

            if ($paralax->nodeValue == 'Verb Forms') {
                $newData = $paralax->parentNode->parentNode;
                $newData = $dom->saveXML($newData);
                $dom2 = new \DOMDocument();
                $dom2->loadHTML($newData);
                $clearData2 = $dom2->getElementById($wordName.'_vpgs_1');
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName.'_unbox_1');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName.'2_unbox_1');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName.'3_unbox_1');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName.'_topg_2');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName.'_unbox_2');
                }
                if (!$clearData2) {
                    $clearData2 = $dom2->getElementById($wordName.'_topg_1');
                }

                $vebFormsData = $this->getElementsByClass($clearData2, 'td', 'verb_form');

                $replacement = array("present simple I / you / we / they ", "he / she / it ", "past simple ",
                    "past participle ", "-ing form ");
                $i=0;

                if (count($vebFormsData) == 5) {
                    foreach($vebFormsData as $vebForm) {
                        if ($i == 0) {
                            $prsi = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 1) {
                            $prsh = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 2) {
                            $pas = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 3) {
                            $pasp = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 4) {
                            $ing = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        $i++;
                        $veb_forms .= trim(str_replace($replacement, "",$vebForm->nodeValue)) . ', ';
                    }
                } else {
                    foreach($vebFormsData as $vebForm) {
                        if ($i == 0) {
                            $prsi = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 1) {
                            $prsh = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 2) {
                            $pas = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 3) {
                            $pasp = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 4) {
                            $pas2 = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 5) {
                            $pasp2 = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        if ($i == 6) {
                            $ing = trim(str_replace($replacement, "",$vebForm->nodeValue));
                        }
                        $i++;
                        $veb_forms .= trim(str_replace($replacement, "",$vebForm->nodeValue)) . ', ';
                    }
                }


            }
        }

        $veb_forms = mb_substr($veb_forms,0,mb_strlen($veb_forms)-2 );
        echo '///////////////////////////////////////////////////////////////////////////////////////////////';
        echo $veb_forms;
        echo '///////////////////////////////////////////////////////////////////////////////////////////////';
        $data = $this->getElementsByClass($clearData, 'a', 'ref');
        foreach($data as $paralax) {
            $check = WordToParse::where('name', $paralax->nodeValue)->first();
            $checkWord = Word::where('prsi', $paralax->nodeValue)
                ->orWhere('prsh', $paralax->nodeValue)
                ->orWhere('pas', $paralax->nodeValue)
                ->orWhere('pasp', $paralax->nodeValue)
                ->orWhere('ing', $paralax->nodeValue)
                ->orWhere('comparative', $paralax->nodeValue)
                ->orWhere('superlative', $paralax->nodeValue)
                ->orWhere('plural', $wordName)
                ->first();
            if (!$check && !$checkWord) {
                WordToParse::create(['url'=>$paralax->getAttribute('href'), 'name' => $paralax->nodeValue]);
            }
            $paralax->setAttribute('href', '/word/'.mb_strtolower(preg_replace('/\s+/', '_', $paralax->nodeValue)));
        }
        $data = $this->getElementsByClass($clearData, 'span', 'xr-g');
        foreach($data as $paralax) {
            $paralax->removeAttribute('href');
        }
        $data = $this->getElementsByClass($clearData, 'span', 'def');
        foreach($data as $paralax) {
            $paralax->removeAttribute('htag');
            $paralax->removeAttribute('hclass');
        }
        $data = $this->getElementsByClass($clearData, 'h2', 'shcut');
        foreach($data as $paralax) {
            $paralax->removeAttribute('htag');
            $paralax->removeAttribute('hclass');
            $paralax->removeAttribute('id');
        }
        $data = $this->getElementsByClass($clearData, 'span', 'grammar');
        foreach($data as $paralax) {
            $paralax->removeAttribute('htag');
            $paralax->removeAttribute('hclass');
        }
        $data = $this->getElementsByClass($clearData, 'span', 'shcut-g');
        foreach($data as $paralax) {
            $paralax->removeAttribute('id');
        }

        $clearDataRing = $dom->getElementById('ring-links-box');
        $clearDataRing->parentNode->removeChild($clearDataRing);
        $data = $dom->saveXML($clearData);
        $translate = '';

        if (!$translate) {
            $baseService = new BaseService('https://e2u.org.ua/s?w=', [], false,
                'dict');
            $result = $baseService->getContents( $wordName.'&dicts=all');
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
                foreach($dataUa as $paralax) {
                    $translate .= $paralax->nodeValue . ', ';
                }
                $translate = stristr($translate, '1');
                $pos = strpos($translate, '2');
                $translate =  substr($translate, 0, $pos);
                $translate = mb_substr($translate,2,mb_strlen($translate) );
            }
        }

        if (!$translate) {
            $baseService = new BaseService('https://dict.com/%D0%B0%D0%BD%D0%B3%D0%BB%D1%96%D0%B8%D1%81%D1%8C%D0%BA%D0%BE-%D1%83%D0%BA%D1%80%D0%B0%D1%96%D0%BD%D1%81%D1%8C%D0%BA%D0%B8%D0%B8/', [], false,
                'dict');
            $result = $baseService->getContents($wordName);
            libxml_use_internal_errors(true);
            $domUa = new \DOMDocument();
            $domUa->loadHTML($result);
            $clearDataUa = $domUa->getElementById('entry-wrapper');


            $dataUa = $this->getElementsByClass($clearDataUa, 'span', 'lex_ful_tran');
            foreach ($dataUa as $paralax) {
                $translate .= $paralax->nodeValue . ', ';
            }
            if (!$translate) {
                $dataUa = $this->getElementsByClass($clearDataUa, 'span', 'lex_ful_coll2t');
                foreach ($dataUa as $paralax) {
                    $translate .= $paralax->nodeValue . ', ';
                }
            }
        }


        $translate = mb_substr($translate,0,mb_strlen($translate)-2 );
//        $translate = '';
        echo $translate;
        echo $data;
//        dd(strlen($data));
        $plural = '';
        if ($wordName != trim($request->get('name'))) {
            $plural = $request->get('name');
        }
        $word = Word::create(['name'=> $wordName, 'type' => $wordType, 'description' => $data, 'veb_forms'=>$veb_forms,
            'translate' => $translate,  'comparative' =>$comparative, 'superlative' => $superlative, 'prsi' => $prsi,
            'prsh' => $prsh, 'pas' => $pas, 'pas2' => $pas2, 'pasp' => $pasp, 'pasp2' => $pasp2, 'ing'=>$ing,
            'plural' => $plural]);
        $user= User::find(2);
        $user->words()->attach($word->id);
        return;
        $user = auth()->user();

        return response()->json(['status' => 'success', 'data' => $user],200);

    }

    function getUserWords(Request $request) {
        $authUser = Auth::id();
        $words = User::where('users.id',$authUser)
            ->leftJoin('user_word', 'user_word.user_id', '=', 'users.id')
            ->leftJoin('word', 'user_word.word_id', '=', 'word.id')
            ->select('word.name', 'user_word.*', 'word.id', 'word.translate')
            ->get();

        return response()->json(['status' => 'success', 'data' => $words],200);
    }

    function voice(Request $request, $id) {
        $filePath = storage_path() . '/app/' . $id;
        $filePath = str_replace('_mp3', '.mp3', $filePath);
        return response()->file( $filePath );
    }

    function getElementsByClass(&$parentNode, $tagName, $className) {
        $nodes=array();

        $childNodeList = $parentNode->getElementsByTagName($tagName);
        for ($i = 0; $i < $childNodeList->length; $i++) {
            $temp = $childNodeList->item($i);
            if (stripos($temp->getAttribute('class'), $className) !== false) {
                $nodes[]=$temp;
            }
        }

        return $nodes;
    }
}
