<?php

class ZangF_Helper
{
    public static function showthumb($string, $type='link', $class='img_thumb', $timthumb='timthumb',$link='', $title='', $alt=''){
        $imgs = unserialize($string);
        $c  = count($imgs);
        if($type=='link') return $imgs[0];
        $text = '';
        if ($c==1)
		{
			$text .= 
			'
				<span class="thu_sty_1">
					<figure itemprop="associatedMedia" data-index="0" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[0].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
					</figure>
				</span>
			';
			return $text;
		};
		if ($c==2)
		{
			$text .= 
			'
				<span class="thu_sty_2">
					<span class="col-1">
						<figure itemprop="associatedMedia" data-index="0" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[0].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
						</figure>	
					</span>
					<span class="col-2">
						<figure itemprop="associatedMedia" data-index="1" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[1].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
						</figure>
					</span>
				</span>
			';
			return $text;
		};
		if ($c==3)
		{
			$text .= 
			'
				<span class="thu_sty_3">
					<span class="row1">
						<figure itemprop="associatedMedia" data-index="0" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[0].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
						</figure>	
					</span>
					<span class="row2">
						<figure itemprop="associatedMedia" data-index="1" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[1].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
						</figure>
						<figure itemprop="associatedMedia" data-index="2" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[2].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
						</figure>
					</span>
				</span>
			';
			return $text;
		};
		if ($c>=4)
		{
			$text .= 
			'
				<span class="thu_sty_4">
					<span class="col-1">
						<figure itemprop="associatedMedia" data-index="0" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[0].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
						</figure>
						<figure itemprop="associatedMedia" data-index="1" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[1].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
						</figure>
						<figure itemprop="associatedMedia" data-index="2" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[2].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
						</figure>						
					</span>
					<span class="col-2">
						<figure itemprop="associatedMedia" data-index="3" itemscope="" itemtype="http://schema.org/ImageObject">
						<a href="'.$link.'">
							<img src="'.$imgs[3].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />
						</a>
						</figure>
					</span>
				</span>
			';
			return $text;
		};
		
		/*
		if($timthumb=='timthumb')
        {
           
			$text .= '<img src="timthumb.php?src='.$imgs[$i].'&h=300&w=300&zc=1" style="max-width: '.$c.'%" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />';
            return $text;
        } else {
			$text .= '<img src="'.$imgs[$i].'" class="'.$class.'" title="'.$title.'" alt="'.$alt.'" />';
            return $text;
				}
        }
		*/
    }
}