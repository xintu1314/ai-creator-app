import { useState } from 'react';
import Sidebar from './sections/Sidebar';
import Header from './sections/Header';
import CreationArea from './sections/CreationArea';
import TemplateCards from './sections/TemplateCards';
import TemplateSheet from './sections/TemplateSheet';
import InspirationLibrary from './sections/InspirationLibrary';
import Assets from './sections/Assets';
import Publish from './sections/Publish';
import Tutorial from './sections/Tutorial';
import type { Template } from './types';
import './App.css';

// Image Templates
const imageTemplates: Template[] = [
  { 
    id: 'img-1', 
    title: '周四周四，生不如死', 
    image: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop', 
    model: '全能图片模型V2',
    type: 'image'
  },
  { 
    id: 'img-2', 
    title: '圣诞海报', 
    image: 'https://images.unsplash.com/photo-1576919228236-a097c32a5cd4?w=400&h=500&fit=crop', 
    model: '全能图片模型V2',
    type: 'image'
  },
  { 
    id: 'img-3', 
    title: '大雪猫猫节气海报', 
    image: 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400&h=500&fit=crop', 
    model: '全能图片模型V2',
    type: 'image'
  },
  { 
    id: 'img-4', 
    title: 'Z-Image-3D卡通', 
    image: 'https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?w=400&h=500&fit=crop', 
    model: 'Z-Image Turbo',
    type: 'image'
  },
  { 
    id: 'img-5', 
    title: '山水画风格', 
    image: 'https://images.unsplash.com/photo-1515405295579-ba7b45403062?w=400&h=500&fit=crop', 
    model: 'Seedream 4.5',
    type: 'image'
  },
  { 
    id: 'img-6', 
    title: '产品展示图', 
    image: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=500&fit=crop', 
    model: 'Seedream 4.5',
    type: 'image'
  },
  { 
    id: 'img-7', 
    title: '充气汽车', 
    image: 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=400&h=500&fit=crop', 
    model: '全能图片模型V2',
    type: 'image'
  },
  { 
    id: 'img-8', 
    title: '护肤品海报合成图', 
    image: 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=400&h=500&fit=crop', 
    model: 'Seedream 4.5',
    type: 'image'
  },
];

// Video Templates
const videoTemplates: Template[] = [
  { 
    id: 'vid-1', 
    title: '蝴蝶香氛', 
    image: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=500&fit=crop', 
    model: 'PixVerse V5',
    type: 'video'
  },
  { 
    id: 'vid-2', 
    title: '生活不易，猫猫打工', 
    image: 'https://images.unsplash.com/photo-1513245543132-31f507417b26?w=400&h=500&fit=crop', 
    model: 'PixVerse V5',
    type: 'video'
  },
  { 
    id: 'vid-3', 
    title: '万物皆可猫猫头', 
    image: 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=400&h=500&fit=crop', 
    model: 'PixVerse V5',
    type: 'video'
  },
  { 
    id: 'vid-4', 
    title: '打累了就休息', 
    image: 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=400&h=500&fit=crop', 
    model: 'PixVerse V5',
    type: 'video'
  },
  { 
    id: 'vid-5', 
    title: '3d小猫蹲厕所', 
    image: 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=400&h=500&fit=crop', 
    model: '海螺 2.3',
    type: 'video'
  },
  { 
    id: 'vid-6', 
    title: 'eyes on you', 
    image: 'https://images.unsplash.com/photo-1494869042583-f6c911f04b4c?w=400&h=500&fit=crop', 
    model: '海螺 2.3',
    type: 'video'
  },
  { 
    id: 'vid-7', 
    title: '水织幻境', 
    image: 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=400&h=500&fit=crop', 
    model: '海螺 2.3',
    type: 'video'
  },
  { 
    id: 'vid-8', 
    title: '砸晕了', 
    image: 'https://images.unsplash.com/photo-1535083783855-76ae62b2914e?w=400&h=500&fit=crop', 
    model: '海螺 2.3',
    type: 'video'
  },
];

function App() {
  const [activeTab, setActiveTab] = useState('create');
  const [creationType, setCreationType] = useState<'image' | 'video'>('image');
  const [showInspirationLibrary, setShowInspirationLibrary] = useState(false);
  const [showTemplateSheet, setShowTemplateSheet] = useState(false);

  const handleTabChange = (tab: string) => {
    setActiveTab(tab);
    // 当切换到其他标签时，关闭灵感库视图
    setShowInspirationLibrary(false);
    setShowTemplateSheet(false);
  };

  const handleUseTemplate = (template: Template) => {
    console.log('Using template:', template);
    // Template logic here
    // If using template, switch to creation area
    if (template.type === 'image' || template.type === 'video') {
      setCreationType(template.type);
      setShowInspirationLibrary(false);
      setShowTemplateSheet(false);
      setActiveTab('create');
    }
  };

  const handleViewMore = () => {
    setShowTemplateSheet(true);
  };

  return (
    <div className="flex h-screen bg-[#F5F5F5]">
      {/* Sidebar */}
      <Sidebar activeTab={activeTab} onTabChange={handleTabChange} />

      {/* Main Content */}
      <div className="flex-1 flex flex-col ml-16">
        {/* Header */}
        <Header onInspirationClick={() => setShowInspirationLibrary(true)} />

        {/* Content Area */}
        <main className="flex-1 mt-14 overflow-auto">
          {showInspirationLibrary ? (
            <InspirationLibrary
              imageTemplates={imageTemplates}
              videoTemplates={videoTemplates}
              onUseTemplate={handleUseTemplate}
            />
          ) : activeTab === 'create' ? (
            <>
              <CreationArea 
                type={creationType} 
                onTypeChange={setCreationType}
                onUseTemplate={handleUseTemplate}
              />

              {/* Templates Section */}
              <div className="max-w-[900px] mx-auto px-6 pb-8">
                <TemplateCards 
                  templates={creationType === 'image' ? imageTemplates : videoTemplates}
                  title="来试试一键做同款"
                  type={creationType}
                  onUseTemplate={handleUseTemplate}
                  onViewMore={handleViewMore}
                />
              </div>
            </>
          ) : activeTab === 'assets' ? (
            <Assets />
          ) : activeTab === 'publish' ? (
            <Publish />
          ) : activeTab === 'tutorial' ? (
            <Tutorial tutorials={[]} />
          ) : null}

          {/* Template Sheet - Half Screen */}
          <TemplateSheet
            open={showTemplateSheet}
            onOpenChange={setShowTemplateSheet}
            templates={creationType === 'image' ? imageTemplates : videoTemplates}
            type={creationType}
            onUseTemplate={handleUseTemplate}
          />
        </main>
      </div>
    </div>
  );
}

export default App;
