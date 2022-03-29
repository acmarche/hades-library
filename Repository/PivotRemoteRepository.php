<?php

namespace AcMarche\Pivot\Repository;

use AcMarche\Pivot\ConnectionPivotTrait;
use AcMarche\Pivot\Pivot;
use AcMarche\Pivot\Utils\Cache;
use Exception;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PivotRemoteRepository
{
    use ConnectionPivotTrait;

    private CacheInterface $cache;

    public const SEPARATOR = '/';

    public function __construct(string $output = Pivot::FORMAT_JSON)
    {
        $this->connect($output);
        $this->cache = Cache::instance();
    }

    /**
     *
     * @throws Exception|TransportExceptionInterface
     */
    public function getOffreById(string $id): string
    {
        $options = [
            'query' => [
                'output' => 'html',
                'page' => 1,
                'fmt' => 'json',
                'info' => true,//labels des specs et relations
                'infolvl' => 0,//de 0 a 10
                'nofmt,' => true,//convertir automatiquement ces contenus HTML en texte brut avec mise en page.
                'content' => Pivot::OFFER_DETAIL_LVL_DEFAULT,//de 0 a 4
            ],
        ];

        //00096Z/exists;fmt=json"
        return $this->executeRequest($this->base_uri.'/offer/'.$id);
    }

    /**
     * tins
     * /thesaurus/typeofr;fmt=xml
     *
     * @throws Exception|TransportExceptionInterface
     */
    public function getThesaurus(string $type): string
    {
        return $this->executeRequest($this->base_uri.'/thesaurus/'.$type);
    }

    /**
     * thesaurus/family/urn:fam:1 ; fmt=xml (fam:1: hebergements)
     * @param string $type
     * @param string|null $sousType
     */
    public function thesaurusFamily(string $type, ?string $sousType): string
    {
        $url = $this->base_uri.'/'.Pivot::THESAURUS_FAMILY.'/'.$type;
        if ($sousType) {
            $url .= '';
        }

        return $this->executeRequest($url);
    }

    /**
     * /thesaurus/typeofr/ 1 ; fmt=xml (1 => hotel)
     * @param int $sousType
     * @return string
     */
    public function thesaurusLogique($sousType): string
    {
        $url = $this->base_uri.'/'.Pivot::THESAURUS_TYPE_OFFRE.'/'.$sousType;

        return $this->executeRequest($url);
    }

    public function thesaurusLocalite(?int $idLocalite = null): string
    {
        $url = $this->base_uri.'/'.Pivot::THESAURUS_TYPE_TINS;
        if ($idLocalite) {
            $url .= '/'.$idLocalite;
        }

        return $this->executeRequest($url);
    }

    /**
     * cp = code postal
     * loc = localité
     * com = commune
     * prv = province
     * mdt = organisme touristique (identifiant de l’organisme)
     * pn = parc naturel (identifiant du parc naturel)
     * mix = recherche sur les colonnes code postal, localité et commune
     * @param int|null $idLocalite
     * @return string
     */
    public function thesaurusLocaliteSearch(string $field, string $value): string
    {
        $url = $this->base_uri.'/'.Pivot::THESAURUS_TYPE_TINS.'/'.$field.'/'.$value;

        return $this->executeRequest($url);
    }

    public function thesaurusCategories(): string
    {
        $url = $this->base_uri.'/cat/urn:fld:filtcat;content=2';

        return $this->executeRequest($url);
    }

    /**
     * tins
     * /thesaurus/typeofr;fmt=xml
     * @throws Exception|TransportExceptionInterface
     */
    public function getImage(string $codeCgt): string
    {
        return $this->executeRequest($this->base_uri.'/img/'.$codeCgt);
    }

    /**
     * Ces requêtes sont créées et stockées par les opérateurs de PIVOT afin de fournir des flux
     * de données. Les requêtes sont accessibles au moyen d’un code identifiant unique (codeCgt).
     * @throws Exception|TransportExceptionInterface
     */
    public function query(string $codeCgt): string
    {
        return $this->executeRequest($this->base_uri.'/query/'.$codeCgt);
    }

    public function search(string $query)
    {
        return $this->executeRequest($this->base_uri.'/search');
    }

    /**
     * @throws Exception
     */
    private function executeRequest(string $url, array $options = [])
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $url,
                $options
            );

            return $response->getContent();
        } catch (ClientException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            // Mailer::sendError('Erreur avec le xml hades', $exception->getMessage());
            throw  new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    private function debug(ResponseInterface $response)
    {
        var_dump($response->getInfo('debug'));
    }
}
