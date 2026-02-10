<?php
function get_templates($type = 'image') {
    $imageTemplates = [
        ['id' => 'img-1', 'title' => '周四周四，生不如死', 'image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop', 'model' => '全能图片模型V2', 'type' => 'image'],
        ['id' => 'img-2', 'title' => '圣诞海报', 'image' => 'https://images.unsplash.com/photo-1576919228236-a097c32a5cd4?w=400&h=500&fit=crop', 'model' => '全能图片模型V2', 'type' => 'image'],
        ['id' => 'img-3', 'title' => '大雪猫猫节气海报', 'image' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400&h=500&fit=crop', 'model' => '全能图片模型V2', 'type' => 'image'],
        ['id' => 'img-4', 'title' => 'Z-Image-3D卡通', 'image' => 'https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?w=400&h=500&fit=crop', 'model' => 'Z-Image Turbo', 'type' => 'image'],
        ['id' => 'img-5', 'title' => '山水画风格', 'image' => 'https://images.unsplash.com/photo-1515405295579-ba7b45403062?w=400&h=500&fit=crop', 'model' => 'Seedream 4.5', 'type' => 'image'],
        ['id' => 'img-6', 'title' => '产品展示图', 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=500&fit=crop', 'model' => 'Seedream 4.5', 'type' => 'image'],
        ['id' => 'img-7', 'title' => '充气汽车', 'image' => 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=400&h=500&fit=crop', 'model' => '全能图片模型V2', 'type' => 'image'],
        ['id' => 'img-8', 'title' => '护肤品海报合成图', 'image' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=400&h=500&fit=crop', 'model' => 'Seedream 4.5', 'type' => 'image'],
    ];
    $videoTemplates = [
        ['id' => 'vid-1', 'title' => '蝴蝶香氛', 'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=500&fit=crop', 'model' => 'PixVerse V5', 'type' => 'video'],
        ['id' => 'vid-2', 'title' => '生活不易，猫猫打工', 'image' => 'https://images.unsplash.com/photo-1513245543132-31f507417b26?w=400&h=500&fit=crop', 'model' => 'PixVerse V5', 'type' => 'video'],
        ['id' => 'vid-3', 'title' => '万物皆可猫猫头', 'image' => 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=400&h=500&fit=crop', 'model' => 'PixVerse V5', 'type' => 'video'],
        ['id' => 'vid-4', 'title' => '打累了就休息', 'image' => 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=400&h=500&fit=crop', 'model' => 'PixVerse V5', 'type' => 'video'],
        ['id' => 'vid-5', 'title' => '3d小猫蹲厕所', 'image' => 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=400&h=500&fit=crop', 'model' => '海螺 2.3', 'type' => 'video'],
        ['id' => 'vid-6', 'title' => 'eyes on you', 'image' => 'https://images.unsplash.com/photo-1494869042583-f6c911f04b4c?w=400&h=500&fit=crop', 'model' => '海螺 2.3', 'type' => 'video'],
        ['id' => 'vid-7', 'title' => '水织幻境', 'image' => 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=400&h=500&fit=crop', 'model' => '海螺 2.3', 'type' => 'video'],
        ['id' => 'vid-8', 'title' => '砸晕了', 'image' => 'https://images.unsplash.com/photo-1535083783855-76ae62b2914e?w=400&h=500&fit=crop', 'model' => '海螺 2.3', 'type' => 'video'],
    ];
    return $type === 'video' ? $videoTemplates : $imageTemplates;
}
