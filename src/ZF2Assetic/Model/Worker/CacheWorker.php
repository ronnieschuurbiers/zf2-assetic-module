<?php
namespace ZF2Assetic\Model\Worker;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;

/**
 * The CacheWorker changes the asset's file names before they are written to the webserver, to prevent caching by the browser.
 *
 * @author Ronnie Schuurbiers, Pronexus
 * @license https://github.com/magnetronnie/zf2-assetic-module/blob/master/LICENSE
 */
class CacheWorker implements WorkerInterface {

	public function process(AssetInterface $assetOrLeaf, AssetFactory $factory) {
		if($assetOrLeaf instanceof AssetCollectionInterface) {
			$path = $assetOrLeaf->getTargetPath();

			$filename = pathinfo($path, PATHINFO_FILENAME);
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$lastModified = $assetOrLeaf->getLastModified();

			if ($lastModified !== null) {
				$path = $filename . '_' . $lastModified . '.' . $ext;
				$assetOrLeaf->setTargetPath($path);
			}
		}
	}
}