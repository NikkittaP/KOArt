<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "paintings".
 *
 * @property int $id
 * @property int $author_id Автор
 * @property string $name Название
 * @property string $description Описание
 * @property int $width Длина
 * @property int $height Высота
 * @property int $ground_id Основа
 * @property string $shopURL Ссылка в магазин
 * @property string $date Дата создания
 * @property double $latitude Широта
 * @property double $longitude Долгота
 * @property string $datetime_add Дата и время добавления
 * @property string $datetime_update Дата и время обновления
 *
 * @property ArtGenresToPainting[] $artGenresToPaintings
 * @property ArtStylesToPainting[] $artStylesToPaintings
 * @property AuthorComments[] $authorComments
 * @property MaterialsToPainting[] $materialsToPaintings
 * @property Authors $author
 * @property Grounds $ground
 * @property Photos[] $photos
 * @property Prices[] $prices
 */
class Paintings extends \yii\db\ActiveRecord
{
    use BilingualTrait;

    // Sale / availability status (column `status`, independent of isVisible).
    const STATUS_AVAILABLE = 1;     // В наличии
    const STATUS_SOLD = 2;          // Продано
    const STATUS_NOT_AVAILABLE = 3; // Нет в наличии

    public $coverPhoto;
    public $coordinates;
    public $groundName;
    public $photo_upload;
    public $artGenreName;
    public $artStyleName;
    public $materials;
    public $price;
    public $size_horizontal;
    public $size_vertical;
    public $authorComments_comments;
    public $authorComments_material_costs;
    public $authorComments_time_costs;
    public $seriesName;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'paintings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            // Title is optional — a sensible default (genre + month) is filled in
            // by the controller when it's left blank. See Paintings::suggestName().
            [['author_id'], 'required'],
            [['author_id', 'ground_id', 'section_id', 'sort_order'], 'integer'],
            // "Order" can be left blank when adding — default it so the NOT NULL
            // column never receives null (lets the author save without filling it).
            [['sort_order'], 'default', 'value' => 0],
            [['section_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sections::className(), 'targetAttribute' => ['section_id' => 'id']],
            [['width', 'height'], 'number'],
            [['description'], 'string'],
            [['date', 'datetime_add', 'datetime_update'], 'safe'],
            [['latitude', 'longitude'], 'number'],
            [['name', 'shopURL'], 'string', 'max' => 255],
            [['author_id'], 'exist', 'skipOnError' => true, 'targetClass' => Authors::className(), 'targetAttribute' => ['author_id' => 'id']],
            [['ground_id'], 'exist', 'skipOnError' => true, 'targetClass' => Grounds::className(), 'targetAttribute' => ['ground_id' => 'id']],
            [['coverPhoto', 'coordinates', 'groundName', 'photo_upload', 'artGenreName', 'artStyleName', 'materials', 'price', 'size_horizontal', 'size_vertical', 'authorComments_comments', 'authorComments_material_costs', 'authorComments_time_costs', 'seriesName'], 'safe'],
			[['isVisible'], 'boolean'],
        ];
        if ($this->hasAttribute('name_en')) {
            $rules[] = [['name_en'], 'string', 'max' => 255];
            $rules[] = [['description_en'], 'string'];
            $rules[] = [['name_en', 'description_en'], 'default', 'value' => null];
        }
        if ($this->hasAttribute('status')) {
            $rules[] = [['status'], 'default', 'value' => self::STATUS_AVAILABLE];
            $rules[] = [['status'], 'in', 'range' => array_keys(self::statuses())];
        }
        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'author_id' => 'Автор',
            'name' => 'Название',
            'name_en' => 'Title (EN)',
            'description' => 'Описание',
            'description_en' => 'Description (EN)',
            'width' => 'Длина, см',
            'height' => 'Высота, см',
            'ground_id' => 'Основа',
            'shopURL' => 'Ссылка в магазин',
            'date' => 'Дата создания',
            'latitude' => 'Широта',
            'longitude' => 'Долгота',
            'datetime_add' => 'Дата и время добавления',
            'datetime_update' => 'Дата и время обновления',
            'coverPhoto' => 'Фото',
            'coordinates' => 'Координаты',
            'groundName' => 'Основа',
            'photo_upload' => 'Загрузка фото',
            'artGenreName' => 'Жанр',
            'artStyleName' => 'Стиль',
            'materials' => 'Материалы',
            'price' => 'Стоимость',
            'size' => 'Размер (ДxВ, см)',
            'size_horizontal' => '[Альбомная ориентация] Размер (ДxВ, см)',
            'size_vertical' => '[Портретная ориентация] Размер (ДxВ, см)',
            'authorComments_comments' => 'Комментарии автора',
            'authorComments_material_costs' => 'Затраты материалов',
            'authorComments_time_costs' => 'Затраты времени',
            'seriesName' => 'Серия',
            'isVisible' => 'Отображать на сайте',
            'section_id' => 'Раздел',
            'sort_order' => 'Порядок сортировки',
            'status' => 'Статус',
        ];
    }

    /**
     * Sale / availability statuses → translated labels (admin category, so the
     * label follows the admin UI language). Used by the editor dropdown, the
     * list column and the filter/bulk controls.
     *
     * @return array status const => label
     */
    public static function statuses()
    {
        return [
            self::STATUS_AVAILABLE => Yii::t('admin', 'Available'),
            self::STATUS_SOLD => Yii::t('admin', 'Sold'),
            self::STATUS_NOT_AVAILABLE => Yii::t('admin', 'Not available'),
        ];
    }

    /** Russian month names, 1-indexed, for the auto title / date labels. */
    public static function ruMonths()
    {
        return [
            1 => 'январь', 2 => 'февраль', 3 => 'март', 4 => 'апрель',
            5 => 'май', 6 => 'июнь', 7 => 'июль', 8 => 'август',
            9 => 'сентябрь', 10 => 'октябрь', 11 => 'ноябрь', 12 => 'декабрь',
        ];
    }

    /**
     * Build a sensible default title from the chosen genre(s) and date, used
     * when the author leaves the title blank (titling every piece is tedious).
     * Examples: "Натюрморт · июнь 2026", "Натюрморт", "Июнь 2026", "Без названия".
     *
     * @param array  $genreNames values from the genre Select2 — each is either a
     *                            numeric genre id or a free-typed new genre name.
     * @param string|null $date   "YYYY-MM" or "YYYY-MM-DD" (may be empty).
     * @return string
     */
    public static function suggestName($genreNames, $date)
    {
        $genre = '';
        if (is_array($genreNames)) {
            foreach ($genreNames as $g) {
                if ($g === null || $g === '') {
                    continue;
                }
                if (is_numeric($g)) {
                    $row = ArtGenres::findOne((int) $g);
                    $genre = $row ? (string) $row->name : '';
                } else {
                    $genre = (string) $g;
                }
                if ($genre !== '') {
                    break;
                }
            }
        }

        $when = '';
        if (!empty($date) && preg_match('/^(\d{4})-(\d{2})/', (string) $date, $m)) {
            $months = self::ruMonths();
            $mi = (int) $m[2];
            $month = $months[$mi] ?? '';
            $when = trim(($month !== '' ? mb_convert_case($month, MB_CASE_TITLE, 'UTF-8') . ' ' : '') . $m[1]);
        }

        if ($genre !== '' && $when !== '') {
            return $genre . ' · ' . $when;
        }
        if ($genre !== '') {
            return $genre;
        }
        if ($when !== '') {
            return $when;
        }
        return 'Без названия';
    }

    /**
     * Human-readable label for this work's status (empty if unset / pre-migration).
     */
    public function getStatusLabel()
    {
        if (!$this->hasAttribute('status')) {
            return '';
        }
        $map = self::statuses();
        return $map[$this->status] ?? '';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['datetime_add', 'datetime_update'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['datetime_update'],
                ],
                // если вместо метки времени UNIX используется datetime:
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArtGenresToPaintings()
    {
        return $this->hasMany(ArtGenresToPainting::className(), ['painting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArtStylesToPaintings()
    {
        return $this->hasMany(ArtStylesToPainting::className(), ['painting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthorComments()
    {
        return $this->hasOne(AuthorComments::className(), ['painting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMaterialsToPaintings()
    {
        return $this->hasMany(MaterialsToPainting::className(), ['painting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(Authors::className(), ['id' => 'author_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGround()
    {
        return $this->hasOne(Grounds::className(), ['id' => 'ground_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhotos()
    {
        return $this->hasMany(Photos::className(), ['painting_id' => 'id']);
    }

    public function getMainPhoto()
    {
        return $this->hasOne(Photos::className(), ['painting_id' => 'id'])->andOnCondition(['isMain' => '1']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPrices()
    {
        return $this->hasMany(Prices::className(), ['painting_id' => 'id']);
    }
    public function getLastPrice($painting_id)
    {
        return Prices::find()->where(['painting_id' => $painting_id])->orderBy(['datetime_add' => SORT_DESC])->one();
    }
    
    public function getPaintingsToSeries()
    {
        return $this->hasMany(PaintingsToSeries::className(), ['painting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSection()
    {
        return $this->hasOne(Sections::className(), ['id' => 'section_id']);
    }
}
