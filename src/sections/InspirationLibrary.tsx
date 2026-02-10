import { useState } from 'react';
import { Image, Video } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Template } from '@/types';

interface InspirationLibraryProps {
  imageTemplates: Template[];
  videoTemplates: Template[];
  onUseTemplate: (template: Template) => void;
}

export default function InspirationLibrary({ 
  imageTemplates, 
  videoTemplates, 
  onUseTemplate 
}: InspirationLibraryProps) {
  const [activeTab, setActiveTab] = useState<'all' | 'image' | 'video'>('all');
  const [selectedCategory, setSelectedCategory] = useState<string>('全部');

  const categories = ['全部', '室内', '景观', '建筑', '电商', '人物', '动物', '自然'];

  const allTemplates = [...imageTemplates, ...videoTemplates];

  const displayTemplates = activeTab === 'all' 
    ? allTemplates 
    : activeTab === 'image' 
      ? imageTemplates 
      : videoTemplates;

  const filteredTemplates = selectedCategory === '全部' 
    ? displayTemplates 
    : displayTemplates.filter(t => t.category === selectedCategory);

  // 当类别切换时，重置选中的模板（如果需要的话）
  const handleCategoryChange = (category: string) => {
    setSelectedCategory(category);
  };

  return (
    <div className="flex-1 p-6 overflow-auto">
      {/* Header */}
      <div className="max-w-[1200px] mx-auto mb-6">
        <h1 className="text-2xl font-semibold text-[#1A1A1A] mb-4">灵感库</h1>
        
        {/* Tabs */}
        <div className="flex gap-4 border-b border-[#E5E5E5] mb-4">
          <button
            onClick={() => setActiveTab('all')}
            className={cn(
              "pb-3 px-1 text-sm font-medium transition-all duration-200 relative",
              activeTab === 'all' 
                ? "text-[#3B82F6]" 
                : "text-[#666666] hover:text-[#1A1A1A]"
            )}
          >
            全部
            {activeTab === 'all' && (
              <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full" />
            )}
          </button>
          <button
            onClick={() => setActiveTab('image')}
            className={cn(
              "pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2",
              activeTab === 'image' 
                ? "text-[#3B82F6]" 
                : "text-[#666666] hover:text-[#1A1A1A]"
            )}
          >
            <Image className="w-4 h-4" />
            图片模板
            {activeTab === 'image' && (
              <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full" />
            )}
          </button>
          <button
            onClick={() => setActiveTab('video')}
            className={cn(
              "pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2",
              activeTab === 'video' 
                ? "text-[#3B82F6]" 
                : "text-[#666666] hover:text-[#1A1A1A]"
            )}
          >
            <Video className="w-4 h-4" />
            视频模板
            {activeTab === 'video' && (
              <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full" />
            )}
          </button>
        </div>

        {/* Category Filter */}
        <div className="flex items-center gap-2 overflow-x-auto">
          {categories.map((category) => (
            <button
              key={category}
              onClick={() => handleCategoryChange(category)}
              className={cn(
                "px-4 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap transition-colors",
                selectedCategory === category
                  ? "bg-[#3B82F6] text-white"
                  : "bg-[#F5F5F5] text-[#666666] hover:bg-[#E5E5E5]"
              )}
            >
              {category}
            </button>
          ))}
        </div>
      </div>

      {/* Masonry Grid */}
      <div className="max-w-[1200px] mx-auto">
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
          {filteredTemplates.map((template) => (
            <div
              key={template.id}
              className="group cursor-pointer"
              onClick={() => onUseTemplate(template)}
            >
              <div className="relative rounded-xl overflow-hidden shadow-md group-hover:shadow-lg transition-all duration-300 aspect-[3/4]">
                {/* Image */}
                <img
                  src={template.image}
                  alt={template.title}
                  className="w-full h-full object-cover"
                />
                
                {/* Model Tag */}
                <div className="absolute top-1.5 left-1.5">
                  <span className="px-1.5 py-0.5 text-[9px] bg-black/60 text-white rounded backdrop-blur-sm">
                    {template.model}
                  </span>
                </div>

                {/* Type Badge */}
                <div className="absolute top-1.5 right-1.5">
                  <span className={cn(
                    "px-1.5 py-0.5 text-[9px] rounded backdrop-blur-sm",
                    template.type === 'image' 
                      ? "bg-blue-500/80 text-white" 
                      : "bg-purple-500/80 text-white"
                  )}>
                    {template.type === 'image' ? '图片' : '视频'}
                  </span>
                </div>

                {/* Title Overlay */}
                <div className="absolute bottom-0 left-0 right-0 p-1.5 bg-gradient-to-t from-black/80 to-transparent">
                  <p className="text-[10px] text-white font-medium line-clamp-2 leading-tight">
                    {template.title}
                  </p>
                </div>

                {/* Hover Overlay */}
                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-250 flex items-center justify-center">
                  <button className="px-4 py-1.5 text-xs font-medium text-white bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition-colors">
                    做同款
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
